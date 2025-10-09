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

interface WebSocketRouteInterface
{
    /**
     * Closes one side of the WebSocket connection.
     *
     * @param array{code?: int, reason?: string} $options
     */
    public function close(array $options = []): void;

    /**
     * Connects to the actual WebSocket server.
     * Returns the server-side WebSocketRoute instance.
     */
    public function connectToServer(): WebSocketRouteInterface;

    /**
     * Handles WebSocket closure.
     * When set, disables default closure forwarding.
     */
    public function onClose(callable $handler): void;

    /**
     * Handles messages sent by WebSocket (from page or server).
     * Stops automatic message forwarding.
     */
    public function onMessage(callable $handler): void;

    /**
     * Sends a message to the WebSocket.
     * Can send to page or server depending on context.
     */
    public function send(string $message): void;

    /**
     * Returns the URL of the WebSocket created in the page.
     */
    public function url(): string;
}
