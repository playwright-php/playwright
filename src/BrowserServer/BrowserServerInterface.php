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

namespace Playwright\BrowserServer;

/**
 * Represents a remotely launched browser server.
 *
 * The server exposes a WebSocket endpoint for connecting Playwright clients.
 * Lifecycle methods close or terminate the browser process behind that endpoint.
 */
interface BrowserServerInterface
{
    /**
     * Returns the WebSocket endpoint.
     *
     * Connect a Playwright client to this URL from another process.
     * The endpoint remains valid while the server is running.
     */
    public function wsEndpoint(): string;

    /**
     * Closes the browser server.
     *
     * Requests a graceful shutdown from the Playwright server process.
     * Connected clients lose their browser connection once it completes.
     */
    public function close(): void;

    /**
     * Kills the browser server.
     *
     * Terminates the underlying browser process without a graceful shutdown.
     * Connected clients lose their browser connection immediately.
     */
    public function kill(): void;

    /**
     * Returns the server process identifier.
     *
     * A null value means the process identifier is unavailable.
     * The identifier can be used for operating-system level diagnostics.
     */
    public function process(): ?int;
}
