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

use Playwright\WebSocket\Options\WaitForEventOptions;

interface WebSocketInterface
{
    /**
     * Indicates that the web socket has been closed.
     */
    public function isClosed(): bool;

    /**
     * Contains the URL of the WebSocket.
     */
    public function url(): string;

    /**
     * Adds an event listener for a WebSocket event.
     * Supported events: 'close', 'framereceived', 'framesent', 'socketerror'.
     */
    public function on(string $event, callable $listener): void;

    /**
     * Adds a one-time event listener for a WebSocket event.
     */
    public function once(string $event, callable $listener): void;

    /**
     * Removes an event listener for a WebSocket event.
     */
    public function removeListener(string $event, callable $listener): void;

    /**
     * Waits for event to fire and passes its value into the predicate function.
     *
     * @param array<string, mixed>|WaitForEventOptions $options
     *
     * @return array<string, mixed>
     */
    public function waitForEvent(string $event, array|WaitForEventOptions $options = []): array;
}
