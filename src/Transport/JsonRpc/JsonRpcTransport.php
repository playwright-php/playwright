<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport\JsonRpc;

use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Node\NodeBinaryResolver;
use PlaywrightPHP\Transport\ServerManager;
use PlaywrightPHP\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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

    public function __construct(
        private readonly ProcessLauncherInterface $processLauncher,
        private readonly array $config = [],
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function connect(): void
    {
        if ($this->connected) {
            return;
        }

        try {
            if (!isset($this->config['command'])) {
                $nodeResolver = new NodeBinaryResolver();
                $nodePath = $nodeResolver->resolve();
                $command = [$nodePath, (new ServerManager())->getServerScriptPath()];
            } else {
                $command = $this->config['command'];
            }
            $this->process = $this->processLauncher->start(
                $command,
                $this->config['cwd'] ?? null,
                $this->config['env'] ?? [],
                $this->config['timeout'] ?? null
            );

            $this->client = new ProcessJsonRpcClient(
                process: $this->process,
                processLauncher: $this->processLauncher,
                logger: $this->logger
            );

            $this->connected = true;

            $this->logger->info('JSON-RPC transport connected', [
                'pid' => $this->process->getPid(),
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

        if ($this->process && $this->process->isRunning()) {
            $this->process->stop();
            $this->process = null;
        }

        $this->logger->info('JSON-RPC transport disconnected');
    }

    public function send(array $message): array
    {
        $this->ensureConnected();

        try {
            $method = $message['action'] ?? 'unknown';
            $params = $this->extractParams($message);
            $timeout = $this->config['timeout'] ?? null;

            return $this->client->send($method, $params, $timeout ? $timeout * 1000 : null);
        } catch (\Throwable $e) {
            $this->logger->error('JSON-RPC send failed', [
                'error' => $e->getMessage(),
                'method' => $message['action'] ?? 'unknown',
            ]);
            throw $e;
        }
    }

    public function sendAsync(array $message): void
    {
        if (!$this->isConnected()) {
            $this->logger->warning('JSON-RPC transport not connected for async operation', [
                'method' => $message['action'] ?? 'unknown',
            ]);

            return;
        }

        try {
            $method = $message['action'] ?? 'unknown';
            $params = $this->extractParams($message);
            $this->sendFireAndForget($method, $params);
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

    private function extractParams(array $message): array
    {
        $params = $message;
        unset($params['action']);

        return $params;
    }

    private function sendFireAndForget(string $method, array $params): void
    {
        try {
            $this->client->send($method, $params, 100.0);
        } catch (\Throwable $e) {
            $this->logger->debug('Fire-and-forget operation completed or timed out', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function ensureConnected(): void
    {
        if (!$this->isConnected()) {
            throw new NetworkException('JSON-RPC transport not connected');
        }
        $this->processLauncher->ensureRunning($this->process, 'JSON-RPC operation');
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
