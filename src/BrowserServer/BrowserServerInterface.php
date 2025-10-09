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
 * BrowserServer facade for a remotely launched Playwright browser server.
 * In PHP, we expose the wsEndpoint for clients to connect and basic lifecycle controls.
 */
interface BrowserServerInterface
{
    /**
     * WebSocket endpoint URL of the Playwright browser server.
     */
    public function wsEndpoint(): string;

    /**
     * Attempts a graceful server shutdown.
     */
    public function close(): void;

    /**
     * Forcibly kills the server process.
     */
    public function kill(): void;

    /**
     * Returns the server process id if known, null otherwise.
     */
    public function process(): ?int;
}
