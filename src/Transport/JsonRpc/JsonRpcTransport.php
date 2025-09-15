<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport\JsonRpc;

use PlaywrightPHP\Event\EventDispatcherInterface;
use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * JSON-RPC transport implementation that bridges JsonRpcClient with the current transport interface.
 *
 * @experimental
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class JsonRpcTransport implements TransportInterface
{
    private ?Process $process = null;
    private ?JsonRpcClient $client = null;
    private bool $connected = false;
    private LoggerInterface $logger;
    /** @var array<string, EventDispatcherInterface> */
    private array $eventDispatchers = [];
    /** @var array<string, callable> */
    private array $pendingCallbacks = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly ProcessLauncherInterface $processLauncher,
        private readonly array $config = [],
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function addEventDispatcher(string $id, EventDispatcherInterface $dispatcher): void
    {
        $this->eventDispatchers[$id] = $dispatcher;
    }

    public function connect(): void
    {
        if ($this->connected) {
            return;
        }

        try {
            $command = $this->config['command'] ?? null;
            if (!is_array($command)) {
                throw new NetworkException('Command configuration is required and must be array');
            }
            $command = $this->validateCommand(array_values($command));

            $cwd = $this->config['cwd'] ?? null;
            if (null !== $cwd && !is_string($cwd)) {
                throw new NetworkException('Invalid cwd configuration: must be string or null');
            }

            $env = $this->config['env'] ?? [];
            if (!is_array($env)) {
                throw new NetworkException('Invalid env configuration: must be array');
            }
            $env = $this->validateEnvironment($env);

            $timeout = $this->config['timeout'] ?? null;
            if (null !== $timeout && !is_float($timeout) && !is_int($timeout)) {
                throw new NetworkException('Invalid timeout configuration: must be float, int or null');
            }

            $this->process = $this->processLauncher->start(
                $command,
                $cwd,
                $env,
                is_int($timeout) ? (float) $timeout : $timeout
            );

            $this->client = new ProcessJsonRpcClient(
                process: $this->process,
                processLauncher: $this->processLauncher,
                logger: $this->logger
            );

            $this->client->setEventHandler(function (array $event): void {
                $this->handleEvent($event);
            });
            $this->logger->debug('Event handler set up for JSON-RPC client');

            $this->connected = true;

            $this->logger->info('JSON-RPC transport connected', [
                'pid' => $this->process?->getPid(),
            ]);
        } catch (\Throwable $e) {
            throw new NetworkException('Failed to connect JSON-RPC transport: '.$e->getMessage(), 0, $e);
        }
    }

    public function disconnect(): void
    {
        if (!$this->connected) {
            return;
        }

        $this->connected = false;

        if ($this->client) {
            $this->client->cancelPendingRequests();
            $this->client = null;
        }
        
        // Clear pending callbacks
        $this->pendingCallbacks = [];

        if ($this->process && $this->process->isRunning()) {
            // First attempt a graceful terminate via launcher (fast path),
            // then always call Process::stop() to satisfy teardown expectations
            // and ensure the underlying process is fully reaped.
            try {
                $this->processLauncher->terminate($this->process, 0.5);
            } catch (\Throwable) {
                // ignore, will still call stop()
            }
            try {
                $this->process->stop(0.25);
            } catch (\Throwable) {
                // ignore failures on stop during shutdown
            }
            $this->process = null;
        }

        $this->logger->info('JSON-RPC transport disconnected');
    }

    /**
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function send(array $message): array
    {
        $this->ensureConnected();

        try {
            $timeout = $this->config['timeout'] ?? null;
            $timeoutMs = null;
            if (null !== $timeout) {
                if (!is_numeric($timeout)) {
                    throw new NetworkException('Invalid timeout: must be numeric');
                }
                $timeoutMs = (int) ($timeout * 1000);
            }

            if (null === $this->client) {
                throw new NetworkException('JSON-RPC client not available');
            }

            // Check if this might be a callback-coordinated command
            if ($this->isCallbackCommand($message['action'] ?? '')) {
                return $this->handleCallbackCommand($message, $timeoutMs);
            }

            $result = $this->client->sendRaw($message, $timeoutMs);
            
            // Debug: log all waitForPopup responses
            if (($message['action'] ?? '') === 'page.waitForPopup') {
                $this->logger->info('DEBUG: waitForPopup response', [
                    'action' => $message['action'],
                    'response' => $result
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('JSON-RPC send failed', [
                'error' => $e->getMessage(),
                'action' => $message['action'] ?? 'unknown',
            ]);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $message
     */
    public function sendAsync(array $message): void
    {
        if (!$this->isConnected()) {
            $this->logger->warning('JSON-RPC transport not connected for async operation', [
                'method' => $message['action'] ?? 'unknown',
            ]);

            return;
        }

        try {
            if (!isset($message['requestId'])) {
                $message['requestId'] = uniqid('req_async_', true);
            }

            $this->logger->debug('Sending async message', [
                'action' => $message['action'] ?? 'unknown',
                'requestId' => $message['requestId'],
            ]);

            $this->sendAsyncMessage($message);
        } catch (\Throwable $e) {
            $this->logger->warning('JSON-RPC sendAsync failed', [
                'error' => $e->getMessage(),
                'method' => $message['action'] ?? 'unknown',
            ]);
        }
    }

    public function isConnected(): bool
    {
        return $this->connected
            && $this->process
            && $this->process->isRunning()
            && null !== $this->client;
    }

    public function processEvents(): void
    {
        if ($this->isConnected()) {
            $this->logger->debug('Processing events (no-op in JSON-RPC transport)');
        }
    }

    /**
     * @param array<string, mixed> $message
     */
    private function sendAsyncMessage(array $message): void
    {
        $json = json_encode($message, JSON_THROW_ON_ERROR);
        $framedMessage = LspFraming::encode($json);

        $inputStream = $this->processLauncher->getInputStream();
        if ($inputStream instanceof InputStream) {
            $inputStream->write($framedMessage);
        } else {
            throw new NetworkException('No input stream available for async message');
        }
    }

    private function ensureConnected(): void
    {
        if (!$this->isConnected()) {
            throw new NetworkException('JSON-RPC transport not connected');
        }
        if (null === $this->process) {
            throw new NetworkException('Process not available');
        }
        $this->processLauncher->ensureRunning($this->process, 'JSON-RPC operation');
    }

    /**
     * @param array<string, mixed> $event
     */
    private function handleEvent(array $event): void
    {
        $this->logger->debug('JsonRpcTransport received event', [
            'event' => $event['event'] ?? 'unknown',
            'objectId' => $event['objectId'] ?? 'missing',
        ]);

        if (!isset($event['objectId'])) {
            $this->logger->warning('Event missing objectId', ['event' => $event]);

            return;
        }

        $objectId = $event['objectId'];
        if (!is_string($objectId)) {
            $this->logger->warning('Invalid objectId in event', ['event' => $event]);

            return;
        }

        if (isset($this->eventDispatchers[$objectId])) {
            $this->logger->debug('Dispatching event to registered handler', [
                'objectId' => $objectId,
                'event' => $event['event'] ?? 'unknown',
            ]);
            $eventName = $event['event'] ?? null;
            $eventParams = $event['params'] ?? [];
            if (!is_string($eventName)) {
                $this->logger->warning('Invalid event name', ['event' => $event]);

                return;
            }
            if (!is_array($eventParams)) {
                $this->logger->warning('Invalid event params', ['event' => $event]);

                return;
            }
            $this->eventDispatchers[$objectId]->dispatchEvent($eventName, $this->validateEventParams($eventParams));
        } else {
            $this->logger->debug('No event dispatcher registered for objectId', [
                'objectId' => $objectId,
                'availableDispatchers' => array_keys($this->eventDispatchers),
            ]);
        }
    }

    /**
     * Store a callback for later execution during coordination
     */
    public function storePendingCallback(string $requestId, callable $callback): void
    {
        $this->pendingCallbacks[$requestId] = $callback;
        $this->logger->debug('Stored pending callback', ['requestId' => $requestId]);
    }

    /**
     * Check if action requires callback coordination
     */
    private function isCallbackCommand(string $action): bool
    {
        return in_array($action, [
            'page.waitForPopup',
            'context.waitForPopup',
            'page.waitForDownload',
            'page.waitForFileChooser'
        ], true);
    }

    /**
     * Handle callback-coordinated command
     *
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    private function handleCallbackCommand(array $message, ?int $timeoutMs): array
    {
        $requestId = $message['requestId'] ?? uniqid('callback_', true);
        $message['requestId'] = $requestId;
        
        $this->logger->info('Handling callback command', [
            'action' => $message['action'],
            'requestId' => $requestId
        ]);
        
        // Send initial message to server
        $response = $this->client->sendRaw($message, $timeoutMs);
        
        echo "DEBUG: Callback command response: " . json_encode($response) . "\n";
        
        // Check if server is waiting for callback
        if (isset($response['type']) && 'callback' === $response['type']) {
            $this->logger->info('Server requested callback', [
                'requestId' => $requestId,
                'callbackType' => $response['callbackType'] ?? 'unknown'
            ]);
            
            // Execute the stored callback
            echo "DEBUG: About to execute callback\n";
            $this->executeCallback($response);
            echo "DEBUG: Callback execution completed\n";
            
            // Continue coordination by sending callback completion
            $continueMessage = [
                'action' => 'callback.continue',
                'requestId' => $requestId,
                'callbackResult' => ['executed' => true]
            ];
            
            $finalResponse = $this->client->sendRaw($continueMessage, $timeoutMs);
            
            // Clean up stored callback
            unset($this->pendingCallbacks[$requestId]);
            
            return $finalResponse;
        }
        
        // No callback required, return response directly
        return $response;
    }

    /**
     * Execute callback based on callback data from server
     *
     * @param array<string, mixed> $callbackData
     */
    private function executeCallback(array $callbackData): void
    {
        $requestId = $callbackData['requestId'] ?? '';
        $callbackType = $callbackData['callbackType'] ?? '';
        
        echo "DEBUG: executeCallback requestId=$requestId, callbackType=$callbackType\n";
        echo "DEBUG: pending callbacks: " . json_encode(array_keys($this->pendingCallbacks)) . "\n";
        
        switch ($callbackType) {
            case 'readyForAction':
                // Server is ready - execute the stored action
                if (isset($this->pendingCallbacks[$requestId])) {
                    echo "DEBUG: Found pending callback, executing...\n";
                    $callback = $this->pendingCallbacks[$requestId];
                    $callback();
                    echo "DEBUG: Callback executed successfully\n";
                } else {
                    echo "DEBUG: No pending callback found for requestId=$requestId\n";
                }
                break;
                
            default:
                echo "DEBUG: Unknown callback type: $callbackType\n";
                break;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @param list<mixed> $command
     *
     * @return list<string>
     */
    private function validateCommand(array $command): array
    {
        $stringCommand = [];
        foreach ($command as $part) {
            if (!is_string($part)) {
                throw new NetworkException('Invalid command configuration: command must be an array of strings.');
            }
            $stringCommand[] = $part;
        }

        /* @phpstan-var list<string> $stringCommand */
        return $stringCommand;
    }

    /**
     * @param array<mixed, mixed> $env
     *
     * @return array<string, string>
     */
    private function validateEnvironment(array $env): array
    {
        $stringEnv = [];
        foreach ($env as $key => $value) {
            if (is_string($value) || is_int($value)) {
                $stringEnv[(string) $key] = (string) $value;
            }
        }

        /* @phpstan-var array<string, string> $stringEnv */
        return $stringEnv;
    }

    /**
     * @param array<mixed, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function validateEventParams(array $params): array
    {
        /* @phpstan-var array<string, mixed> $params */
        return $params;
    }
}
