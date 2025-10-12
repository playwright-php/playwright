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
 * Deterministic in-memory implementation of the Playwright transport interface.
 *
 * Allows tests to queue scripted responses, inspect sent messages, and simulate
 * async/event flows without starting the real Playwright server.
 */
final class MockTransport implements TransportInterface
{
    private bool $connected = false;

    /**
     * @var array<int, array{
     *     matcher: (callable(array<string, mixed>, self): bool)|callable|null,
     *     payload: array<string, mixed>|callable|\Throwable
     * }>
     */
    private array $scriptedResponses = [];

    /** @var array<int, array<string, mixed>> */
    private array $sentMessages = [];

    /** @var array<int, array<string, mixed>> */
    private array $asyncMessages = [];

    /**
     * @var list<callable(array<string, mixed>, self): void>
     */
    private array $asyncHandlers = [];

    /**
     * @var list<callable(self): mixed>
     */
    private array $eventCallbacks = [];

    /** @var list<mixed> */
    private array $processedEventResults = [];

    /** @var array<string, callable> */
    private array $pendingRequestCallbacks = [];

    public function connect(): void
    {
        $this->connected = true;
    }

    public function disconnect(): void
    {
        $this->connected = false;
    }

    /**
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function send(array $message): array
    {
        $this->ensureConnected();
        $this->sentMessages[] = $message;

        foreach ($this->scriptedResponses as $index => $script) {
            if (null !== $script['matcher'] && !$this->invokeMatcher($script['matcher'], $message)) {
                continue;
            }

            unset($this->scriptedResponses[$index]);
            $this->scriptedResponses = array_values($this->scriptedResponses);

            return $this->resolvePayload($script['payload'], $message);
        }

        throw new \UnderflowException('No scripted response matches message '.$this->describeMessage($message));
    }

    /**
     * @param array<string, mixed> $message
     */
    public function sendAsync(array $message): void
    {
        $this->ensureConnected();
        $this->asyncMessages[] = $message;

        foreach ($this->asyncHandlers as $handler) {
            $this->invokeCallable($handler, $message);
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function processEvents(): void
    {
        $this->ensureConnected();

        while ($event = array_shift($this->eventCallbacks)) {
            $this->processedEventResults[] = $this->invokeEventCallback($event);
        }
    }

    /**
     * Queue a scripted response for the next {@see send()} call.
     *
     * @param array<string, mixed>|callable|\Throwable $payload
     * @param callable|null                            $matcher accepts signatures with zero, one (message) or two (message, transport) parameters
     */
    public function queueResponse(array|callable|\Throwable $payload, ?callable $matcher = null): void
    {
        $this->scriptedResponses[] = [
            'matcher' => $matcher,
            'payload' => $payload,
        ];
    }

    /**
     * Register a callback invoked on every {@see sendAsync()} call.
     *
     * @param callable $handler accepts signatures with zero, one (message), or two (message, transport) parameters
     */
    public function onSendAsync(callable $handler): void
    {
        $this->asyncHandlers[] = $handler;
    }

    /**
     * Queue an event callback executed from {@see processEvents()}.
     *
     * @param callable $callback accepts signatures with zero parameters or one (transport) parameter
     */
    public function queueProcessEvent(callable $callback): void
    {
        $this->eventCallbacks[] = $callback;
    }

    /**
     * Reset recorded history while keeping scripted responses and queued events.
     */
    public function resetHistory(): void
    {
        $this->sentMessages = [];
        $this->asyncMessages = [];
        $this->processedEventResults = [];
    }

    public function storePendingCallback(string $requestId, callable $callback): void
    {
        $this->pendingRequestCallbacks[$requestId] = $callback;
    }

    /**
     * @return array<string, callable>
     */
    public function getStoredPendingCallbacks(): array
    {
        return $this->pendingRequestCallbacks;
    }

    /**
     * @param array<string, mixed> $message
     */
    public function executePendingCallback(string $requestId, array $message = []): mixed
    {
        if (!array_key_exists($requestId, $this->pendingRequestCallbacks)) {
            throw new \OutOfBoundsException(sprintf('No pending callback stored for request "%s"', $requestId));
        }

        return $this->invokeCallable($this->pendingRequestCallbacks[$requestId], $message);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSentMessages(): array
    {
        return $this->sentMessages;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getAsyncMessages(): array
    {
        return $this->asyncMessages;
    }

    /**
     * @return list<mixed>
     */
    public function getProcessedEventResults(): array
    {
        return $this->processedEventResults;
    }

    public function getPendingResponseCount(): int
    {
        return count($this->scriptedResponses);
    }

    public function getPendingEventCount(): int
    {
        return count($this->eventCallbacks);
    }

    /**
     * @param array<string, mixed> $message
     */
    private function invokeMatcher(callable $matcher, array $message): bool
    {
        $result = $this->invokeCallable($matcher, $message);

        if (!is_bool($result)) {
            throw new \UnexpectedValueException('Mock transport matcher must return a boolean.');
        }

        return $result;
    }

    /**
     * @param array<string, mixed>|callable|\Throwable $payload
     * @param array<string, mixed>                     $message
     *
     * @return array<string, mixed>
     */
    private function resolvePayload(array|callable|\Throwable $payload, array $message): array
    {
        if ($payload instanceof \Throwable) {
            throw $payload;
        }

        if (is_callable($payload)) {
            $result = $this->invokeCallable($payload, $message);

            if (!is_array($result)) {
                throw new \UnexpectedValueException('Mock transport response callback must return an array.');
            }

            /* @var array<string, mixed> $result */
            return $result;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $message
     */
    private function invokeCallable(callable $callable, array $message): mixed
    {
        $closure = $callable instanceof \Closure ? $callable : \Closure::fromCallable($callable);
        $reflection = new \ReflectionFunction($closure);
        $parameterCount = $reflection->getNumberOfParameters();

        return match (true) {
            $parameterCount >= 2 => $closure($message, $this),
            1 === $parameterCount => $closure($message),
            default => $closure(),
        };
    }

    private function invokeEventCallback(callable $callback): mixed
    {
        $closure = $callback instanceof \Closure ? $callback : \Closure::fromCallable($callback);
        $reflection = new \ReflectionFunction($closure);

        return $reflection->getNumberOfParameters() >= 1 ? $closure($this) : $closure();
    }

    /**
     * @param array<string, mixed> $message
     */
    private function describeMessage(array $message): string
    {
        $action = $message['action'] ?? null;
        if (is_string($action) && '' !== $action) {
            return sprintf('[action=%s]', $action);
        }

        $encoded = json_encode($message);
        if (false === $encoded) {
            return '[unserializable-message]';
        }

        return $encoded;
    }

    private function ensureConnected(): void
    {
        if (!$this->connected) {
            throw new \UnexpectedValueException('Mock transport is not connected.');
        }
    }
}
