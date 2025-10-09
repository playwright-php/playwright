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

namespace Playwright\Browser;

interface BrowserServerInterface
{
    /**
     * Closes the browser gracefully and makes sure the process is terminated.
     */
    public function close(): void;

    /**
     * Kills the browser process and waits for the process to exit.
     */
    public function kill(): void;

    /**
     * Spawned browser application process.
     *
     * @return resource|null
     */
    public function process();

    /**
     * Browser websocket url which can be used to connect to the browser.
     */
    public function wsEndpoint(): string;
}
