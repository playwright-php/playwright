<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport;

use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Exception\TimeoutException;
use PlaywrightPHP\Node\NodeBinaryResolver;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class ProcessTransport implements TransportInterface
{
    private ?Process $process = null;
    private ?InputStream $inputStream = null;
    private LoggerInterface $logger;
    private array $config;
    private bool $connected = false;

    /** @var callable[] */
    private array $pendingRequests = [];
    private string $buffer = '';
    private array $eventDispatchers = [];

    public function __construct(array $config = [], ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();
    }

    public function addEventDispatcher(string $id, object $dispatcher): void
    {
        $this->eventDispatchers[$id] = $dispatcher;
    }

    public function connect(): void
    {
        if ($this->connected) {
            return;
        }

        try {
            $this->inputStream = new InputStream();

            if (!isset($this->config['command'])) {
                $nodeResolver = new NodeBinaryResolver();
                $nodePath = $nodeResolver->resolve();
                $command = [$nodePath, (new ServerManager())->getServerScriptPath()];
            } else {
                $command = $this->config['command'];
            }

            $cwd = $this->config['cwd'] ?? null;
            $env = $this->config['env'] ?? null;

            $this->process = new Process(
                $command,
                $cwd,
                $env,
                null,
                60
            );
            $this->process->setInput($this->inputStream);
            $this->process->start();

            $ready = false;
            $start = microtime(true);
            while ((microtime(true) - $start) < 10) {
                $output = $this->process->getIncrementalOutput();
                if (false !== strpos($output, 'READY')) {
                    $ready = true;
                    break;
                }
                usleep(10000);
            }
            if (!$ready) {
                throw new NetworkException('Playwright server did not start or respond with READY');
            }
            $this->connected = true;
        } catch (\Exception $e) {
            throw new NetworkException('Failed to start Playwright server: '.$e->getMessage(), 0, $e);
        }
    }

    private function readOutput(): void
    {
        $this->buffer .= $this->process->getIncrementalOutput();
        $errorOutput = $this->process->getIncrementalErrorOutput();

        if ($errorOutput) {
            $this->logger->error('SERVER STDERR: '.$errorOutput);
            if ($this->config['verbose'] ?? false) {
                file_put_contents(
                    dirname(__DIR__, 2).'/bin/playwright-server.log',
                    date('c').' SERVER STDERR: '.$errorOutput."\n",
                    FILE_APPEND | LOCK_EX
                );
            }
        }

        while (($pos = strpos($this->buffer, "\n")) !== false) {
            $line = substr($this->buffer, 0, $pos);
            $this->buffer = substr($this->buffer, $pos + 1);

            if ('READY' === trim($line)) {
                if (isset($this->pendingRequests['ready'])) {
                    $this->pendingRequests['ready'](true);
                    unset($this->pendingRequests['ready']);
                }
                continue;
            }

            if ($this->config['verbose'] ?? false) {
                $this->logger->info('RECV < '.$line);
            }

            $response = json_decode($line, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                $this->logger->warning('Received invalid JSON from server', ['line' => $line]);
                continue;
            }

            if (isset($response['requestId'])) {
                $requestId = $response['requestId'];
                if (isset($this->pendingRequests[$requestId])) {
                    $this->pendingRequests[$requestId]($response);
                    unset($this->pendingRequests[$requestId]);
                }
            } elseif (isset($response['event'])) {
                $this->handleEvent($response);
            }
        }
    }

    private function cleanup(): void
    {
        if ($this->inputStream) {
            $this->inputStream->close();
            $this->inputStream = null;
        }

        if ($this->process) {
            if ($this->process->isRunning()) {
                $this->process->stop();
            }
            $this->process = null;
        }

        $this->connected = false;
        $this->logger->info('Playwright server stopped.');
    }

    private function handleEvent(array $event): void
    {
        $objectId = $event['objectId'];
        if (isset($this->eventDispatchers[$objectId])) {
            $this->eventDispatchers[$objectId]->dispatchEvent($event['event'], $event['params']);
        }
    }

    private function waitForReadySignal(): void
    {
        $result = null;
        $this->pendingRequests['ready'] = function ($res) use (&$result) {
            $result = $res;
        };

        $startTime = microtime(true);
        while (null === $result && (microtime(true) - $startTime) < 15) {
            $this->readOutput();
            usleep(1000);
            if (!$this->process->isRunning()) {
                throw new NetworkException('Playwright server process exited unexpectedly before sending READY signal.');
            }
        }

        if (null === $result) {
            throw new TimeoutException('Playwright server did not send READY signal within 15 seconds.');
        }
    }

    public function disconnect(): void
    {
        $this->cleanup();
    }

    public function send(array $message): array
    {
        if (!$this->isConnected()) {
            throw new NetworkException('Transport not connected');
        }

        $requestId = uniqid('req_', true);
        $message['requestId'] = $requestId;

        $result = null;
        $this->pendingRequests[$requestId] = function ($response) use (&$result) {
            $result = $response;
        };

        $json = json_encode($message);

        if ($this->config['verbose'] ?? false) {
            $this->logger->info('SEND > '.$json);
        } else {
            $this->logger->debug('Sending message', ['action' => $message['action'] ?? 'unknown']);
        }

        $this->inputStream->write($json."\n");

        $timeout = $this->config['timeout'] ?? 30;
        $startTime = microtime(true);

        $pollInterval = 1000; // Start with 1ms
        $maxInterval = 50000; // Max 50ms

        while (null === $result && (microtime(true) - $startTime) < $timeout) {
            $this->readOutput();

            if (null !== $result) {
                break;
            }

            if (!$this->process->isRunning()) {
                throw new NetworkException('Playwright server process exited unexpectedly while waiting for a response.');
            }

            // Adaptive polling: start fast, slow down if needed
            usleep((int) $pollInterval);
            $pollInterval = min($pollInterval * 1.2, $maxInterval);
        }

        if (null === $result) {
            unset($this->pendingRequests[$requestId]);
            throw new TimeoutException(sprintf('Request %s timed out after %d seconds.', $requestId, $timeout));
        }

        return $result;
    }

    public function sendAsync(array $message): void
    {
        if (!$this->isConnected()) {
            throw new NetworkException('Transport not connected');
        }

        if (!isset($message['requestId'])) {
            $message['requestId'] = uniqid('req_async_post_', true);
        }

        $json = json_encode($message);
        $this->logger->debug('Posting message', ['action' => $message['action'] ?? 'unknown']);
        $this->inputStream->write($json."\n");

        $this->readOutput();
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->process && $this->process->isRunning();
    }

    public function processEvents(): void
    {
        if ($this->isConnected()) {
            $this->readOutput();
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
