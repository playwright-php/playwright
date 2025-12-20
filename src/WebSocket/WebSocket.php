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
use Playwright\WebSocket\Options\WaitForEventOptions;

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
                $code = isset($params['code']) && \is_int($params['code']) ? $params['code'] : null;
                $reason = isset($params['reason']) && \is_string($params['reason']) ? $params['reason'] : null;
                $this->emit('close', [['code' => $code, 'reason' => $reason]]);
                break;
            case 'framereceived':
                $payload = isset($params['payload']) && \is_string($params['payload']) ? $params['payload'] : '';
                $this->emit('framereceived', [['payload' => $payload]]);
                break;
            case 'framesent':
                $payload = isset($params['payload']) && \is_string($params['payload']) ? $params['payload'] : '';
                $this->emit('framesent', [['payload' => $payload]]);
                break;
            case 'socketerror':
                $error = isset($params['error']) && \is_string($params['error']) ? $params['error'] : 'Unknown socket error';
                $this->emit('socketerror', [['error' => $error]]);
                break;
            default:
                $this->emit($eventName, [$params]);
        }
    }

    /**
     * Wait locally for an event, optionally filtered by predicate, with timeout.
     * Falls back to pumping transport events via processEvents.
     *
     * @param array<string, mixed>|WaitForEventOptions $options
     *
     * @return array<string, mixed>
     */
    public function waitForEvent(string $event, array|WaitForEventOptions $options = []): array
    {
        $options = WaitForEventOptions::from($options);
        $timeout = isset($options->timeout) ? (int) $options->timeout : 30000;
        $predicate = isset($options->predicate) && \is_callable($options->predicate) ? $options->predicate : null;

        $resolved = false;
        /** @var array<string, mixed> $result */
        $result = [];

        $listener = function ($eventData) use (&$resolved, &$result, $predicate): void {
            if (null !== $predicate) {
                try {
                    if (!$predicate($eventData)) {
                        return;
                    }
                } catch (\Throwable) {
                    return;
                }
            }
            if (\is_array($eventData)) {
                $normalized = [];
                foreach ($eventData as $key => $value) {
                    $normalized[\is_string($key) ? $key : (string) $key] = $value;
                }
                $result = $normalized;
            } else {
                $result = ['value' => $eventData];
            }
            $resolved = true;
        };

        $this->on($event, $listener);

        $start = (int) \floor(microtime(true) * 1000);
        while (!$resolved) {
            $this->transport->processEvents();
            usleep(1000);

            $now = (int) \floor(microtime(true) * 1000);
            if ($now - $start > $timeout) {
                $this->removeListener($event, $listener);
                throw new TimeoutException(\sprintf('Timeout %dms exceeded while waiting for WebSocket event "%s"', $timeout, $event));
            }
        }
        $this->removeListener($event, $listener);

        return $result;
    }
}
