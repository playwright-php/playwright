<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport\JsonRpc;

use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Exception\TimeoutException;
use PlaywrightPHP\Transport\ErrorMapper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\ClockInterface;

/**
 * JSON-RPC client for communicating with Playwright processes.
 *
 * @experimental
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class JsonRpcClient implements JsonRpcClientInterface
{
    private int $nextId = 1;

    /** @var array<int, array{method: string, timestamp: float}> */
    private array $pendingRequests = [];

    public function __construct(
        private readonly ClockInterface $clock,
        protected readonly LoggerInterface $logger = new NullLogger(),
        private readonly float $defaultTimeoutMs = 30000.0,
    ) {
    }

    /**
     * Send a JSON-RPC request and wait for response.
     *
     * @param array<string, mixed>|null $params
     *
     * @return array<string, mixed>
     */
    public function send(string $method, ?array $params = null, ?float $timeoutMs = null): array
    {
        $timeoutMs ??= $this->defaultTimeoutMs;
        $id = $this->nextId++;

        $deadline = $timeoutMs > 0
            ? $this->getCurrentTimeMs() + $timeoutMs
            : null;

        
        $this->trackRequest($id, $method);

        $this->logger->debug('Sending JSON-RPC request', [
            'id' => $id,
            'method' => $method,
            'params' => $params ? array_keys($params) : null,
            'timeoutMs' => $timeoutMs,
        ]);

        try {
            $request = [
                'jsonrpc' => '2.0',
                'id' => $id,
                'method' => $method,
                'params' => $params ?? (object) [],
            ];

            $response = $this->sendAndReceive($request, $deadline);

            unset($this->pendingRequests[$id]);

            if (isset($response['error'])) {
                $error = $response['error'];
                if (!is_array($error)) {
                    throw new NetworkException('Invalid error format in JSON-RPC response');
                }

                $typedError = [];
                foreach ($error as $key => $value) {
                    if (!is_string($key)) {
                        throw new NetworkException('Invalid error format in JSON-RPC response: non-string key');
                    }
                    $typedError[$key] = $value;
                }

                throw ErrorMapper::toException($typedError, $method, $params, $timeoutMs);
            }

            if (null !== $deadline && $this->getCurrentTimeMs() > $deadline) {
                throw new TimeoutException(sprintf('Timeout of %.0fms exceeded for %s', $timeoutMs, $method), $timeoutMs, null, ['method' => $method]);
            }

            $result = $response['result'] ?? [];

            return is_array($result) ? $result : [];
        } catch (\Throwable $e) {
            unset($this->pendingRequests[$id]);
            throw $e;
        }
    }

    /**
     * Send a raw message in the original format and wait for response.
     *
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function sendRaw(array $message, ?float $timeoutMs = null): array
    {
        $timeoutMs ??= $this->defaultTimeoutMs;
        $id = $this->nextId++;

        $deadline = $timeoutMs > 0
            ? $this->getCurrentTimeMs() + $timeoutMs
            : null;

        
        $request = $message;
        $request['requestId'] = $id;

        
        $actionString = is_string($message['action'] ?? null) ? $message['action'] : 'unknown';
        $this->trackRequest($id, $actionString);

        $this->logger->debug('Sending raw request', [
            'id' => $id,
            'action' => $message['action'] ?? 'unknown',
            'timeoutMs' => $timeoutMs,
        ]);

        try {
            $response = $this->sendAndReceive($request, $deadline);
            unset($this->pendingRequests[$id]);

            
            return $response;
        } catch (\Throwable $e) {
            unset($this->pendingRequests[$id]);
            throw $e;
        }
    }

    protected function getCurrentTimeMs(): float
    {
        return (float) $this->clock->now()->format('Uu') / 1000;
    }

    /**
     * @param array<string, mixed> $request
     *
     * @return array<string, mixed>
     */
    protected function sendAndReceive(array $request, ?float $deadline): array
    {
        $this->logger->debug('Would send request to process', ['request' => $request]);

        return [
            'jsonrpc' => '2.0',
            'id' => $request['id'],
            'result' => ['status' => 'ok', 'method' => $request['method']],
        ];
    }

    /**
     * @return array<int, array{method: string, timestamp: float, age: float}>
     */
    public function getPendingRequests(): array
    {
        $currentTime = $this->getCurrentTimeMs();
        $pending = [];

        foreach ($this->pendingRequests as $id => $info) {
            $pending[$id] = [
                'method' => $info['method'],
                'timestamp' => $info['timestamp'],
                'age' => $currentTime - $info['timestamp'],
            ];
        }

        return $pending;
    }

    public function cancelPendingRequests(): void
    {
        $this->logger->debug('Canceling pending requests', [
            'count' => count($this->pendingRequests),
        ]);

        $this->pendingRequests = [];
    }

    /**
     * Helper method to properly track requests.
     */
    private function trackRequest(int $id, string $method): void
    {
        $this->pendingRequests[$id] = [
            'method' => $method,
            'timestamp' => $this->getCurrentTimeMs(),
        ];
    }
}
