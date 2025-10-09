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

namespace Playwright\WebSocket;

use Playwright\Event\EventDispatcherInterface;
use Playwright\Event\EventEmitter;
use Playwright\Exception\TimeoutException;
use Playwright\Transport\TransportInterface;

/**
 * WebSocket implementation aligned with Playwright's WebSocket API.
 * Supported events: 'close', 'framereceived', 'framesent', 'socketerror'.
 *
 * @see https://playwright.dev/docs/api/class-websocket
 */
final class WebSocket implements WebSocketInterface, EventDispatcherInterface
{
    use EventEmitter;

    private bool $closed = false;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $socketId,
        private readonly string $socketUrl,
    ) {
        if (\method_exists($this->transport, 'addEventDispatcher')) {
            // Register to receive events for this WebSocket object id
            $this->transport->addEventDispatcher($this->socketId, $this);
        }
    }

    public function url(): string
    {
        return $this->socketUrl;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function dispatchEvent(string $eventName, array $params): void
    {
        switch ($eventName) {
            case 'close':
                $this->closed = true;
                // Expected params: { code?: int, reason?: string }
                $code = isset($params['code']) && \is_int($params['code']) ? $params['code'] : null;
                $reason = isset($params['reason']) && \is_string($params['reason']) ? $params['reason'] : null;
                $this->emit('close', [['code' => $code, 'reason' => $reason]]);
                break;
            case 'framereceived':
                // Expected params: { payload: string }
                $payload = isset($params['payload']) && \is_string($params['payload']) ? $params['payload'] : '';
                $this->emit('framereceived', [['payload' => $payload]]);
                break;
            case 'framesent':
                // Expected params: { payload: string }
                $payload = isset($params['payload']) && \is_string($params['payload']) ? $params['payload'] : '';
                $this->emit('framesent', [['payload' => $payload]]);
                break;
            case 'socketerror':
                // Expected params: { error: string }
                $error = isset($params['error']) && \is_string($params['error']) ? $params['error'] : 'Unknown socket error';
                $this->emit('socketerror', [['error' => $error]]);
                break;
            default:
                // Forward unknown events as-is
                $this->emit($eventName, [$params]);
        }
    }

    /**
     * Wait locally for an event, optionally filtered by predicate, with timeout.
     * Falls back to pumping transport events via processEvents.
     *
     * @param array{predicate?: callable, timeout?: int} $options
     *
     * @return array<string, mixed>
     */
    public function waitForEvent(string $event, array $options = []): array
    {
        $timeout = isset($options['timeout']) && \is_int($options['timeout']) ? $options['timeout'] : 30000;
        $predicate = isset($options['predicate']) && \is_callable($options['predicate']) ? $options['predicate'] : null;

        $resolved = false;
        $result = [];

        $listener = function ($eventData) use (&$resolved, &$result, $predicate): void {
            if (null !== $predicate) {
                try {
                    if (!$predicate($eventData)) {
                        return; // keep waiting
                    }
                } catch (\Throwable) {
                    return; // ignore predicate errors and keep waiting
                }
            }
            $result = \is_array($eventData) ? $eventData : ['value' => $eventData];
            $resolved = true;
        };

        $this->on($event, $listener);

        $start = (int) \floor(microtime(true) * 1000);
        while (!$resolved) {
            // Pump transport to receive events
            $this->transport->processEvents();
            usleep(1000); // 1ms to avoid busy wait

            $now = (int) \floor(microtime(true) * 1000);
            if ($now - $start > $timeout) {
                // Cleanup listener on timeout
                $this->removeListener($event, $listener);
                throw new TimeoutException(\sprintf('Timeout %dms exceeded while waiting for WebSocket event "%s"', $timeout, $event));
            }
        }

        // Cleanup listener after resolution
        $this->removeListener($event, $listener);

        return $result;
    }
}
