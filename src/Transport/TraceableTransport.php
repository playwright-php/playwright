<?php

declare(strict_types=1);

/*
 * This file is part of the community-maintained Playwright PHP project.
 * It is not affiliated with or endorsed by Microsoft.
 *
 * (c) 2025-Present - Playwright PHP - https://github.com/playwright-php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Playwright\Transport;

/**
 * Decorator transport that records every interaction with the inner transport.
 *
 * Useful in unit tests for asserting on payloads without stubbing behaviour.
 */
final class TraceableTransport implements TransportInterface
{
    /**
     * @var list<array{
     *     message: array<string, mixed>,
     *     response: array<string, mixed>|null,
     *     exception: \Throwable|null
     * }>
     */
    private array $sendCalls = [];

    /**
     * @var list<array{
     *     message: array<string, mixed>,
     *     exception: \Throwable|null
     * }>
     */
    private array $asyncCalls = [];

    /**
     * @var list<array{
     *     exception: \Throwable|null
     * }>
     */
    private array $processEventsCalls = [];

    /**
     * @var list<array{
     *     method: 'connect'|'disconnect',
     *     exception: \Throwable|null
     * }>
     */
    private array $connectionCalls = [];

    public function __construct(private readonly TransportInterface $decorated)
    {
    }

    public function connect(): void
    {
        $this->recordConnection('connect', function (): void {
            $this->decorated->connect();
        });
    }

    public function disconnect(): void
    {
        $this->recordConnection('disconnect', function (): void {
            $this->decorated->disconnect();
        });
    }

    /**
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function send(array $message): array
    {
        try {
            $response = $this->decorated->send($message);
            $this->sendCalls[] = [
                'message' => $message,
                'response' => $response,
                'exception' => null,
            ];

            return $response;
        } catch (\Throwable $exception) {
            $this->sendCalls[] = [
                'message' => $message,
                'response' => null,
                'exception' => $exception,
            ];

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $message
     */
    public function sendAsync(array $message): void
    {
        try {
            $this->decorated->sendAsync($message);
            $this->asyncCalls[] = [
                'message' => $message,
                'exception' => null,
            ];
        } catch (\Throwable $exception) {
            $this->asyncCalls[] = [
                'message' => $message,
                'exception' => $exception,
            ];

            throw $exception;
        }
    }

    public function isConnected(): bool
    {
        return $this->decorated->isConnected();
    }

    public function processEvents(): void
    {
        try {
            $this->decorated->processEvents();
            $this->processEventsCalls[] = [
                'exception' => null,
            ];
        } catch (\Throwable $exception) {
            $this->processEventsCalls[] = [
                'exception' => $exception,
            ];

            throw $exception;
        }
    }

    /**
     * Reset all recorded call history.
     */
    public function reset(): void
    {
        $this->sendCalls = [];
        $this->asyncCalls = [];
        $this->processEventsCalls = [];
        $this->connectionCalls = [];
    }

    /**
     * @return list<array{
     *     message: array<string, mixed>,
     *     response: array<string, mixed>|null,
     *     exception: \Throwable|null
     * }>
     */
    public function getSendCalls(): array
    {
        return $this->sendCalls;
    }

    /**
     * @return list<array{
     *     message: array<string, mixed>,
     *     exception: \Throwable|null
     * }>
     */
    public function getAsyncCalls(): array
    {
        return $this->asyncCalls;
    }

    /**
     * @return list<array{
     *     exception: \Throwable|null
     * }>
     */
    public function getProcessEventsCalls(): array
    {
        return $this->processEventsCalls;
    }

    /**
     * @return list<array{
     *     method: 'connect'|'disconnect',
     *     exception: \Throwable|null
     * }>
     */
    public function getConnectionCalls(): array
    {
        return $this->connectionCalls;
    }

    /**
     * @param 'connect'|'disconnect' $method
     * @param callable(): void       $callback
     */
    private function recordConnection(string $method, callable $callback): void
    {
        try {
            $callback();
            $this->connectionCalls[] = [
                'method' => $method,
                'exception' => null,
            ];
        } catch (\Throwable $exception) {
            $this->connectionCalls[] = [
                'method' => $method,
                'exception' => $exception,
            ];

            throw $exception;
        }
    }
}
