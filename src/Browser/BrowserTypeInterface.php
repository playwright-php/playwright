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

interface BrowserTypeInterface
{
    /**
     * Attaches Playwright to an existing browser instance.
     *
     * @param array<string, mixed> $options
     */
    public function connect(string $wsEndpoint, array $options = []): BrowserInterface;

    /**
     * Attaches Playwright to an existing browser instance using the Chrome DevTools Protocol.
     * Only supported for Chromium-based browsers.
     *
     * @param array<string, mixed> $options
     */
    public function connectOverCDP(string $endpointURL, array $options = []): BrowserInterface;

    /**
     * Returns the path where Playwright expects to find a bundled browser executable.
     */
    public function executablePath(): string;

    /**
     * Launches and returns a browser instance.
     *
     * @param array<string, mixed> $options
     */
    public function launch(array $options = []): BrowserInterface;

    /**
     * Launches a browser with persistent storage and returns a browser context instance.
     *
     * @param array<string, mixed> $options
     */
    public function launchPersistentContext(string $userDataDir, array $options = []): BrowserContextInterface;

    /**
     * Launches a browser server that can be connected to later.
     *
     * @param array<string, mixed> $options
     */
    public function launchServer(array $options = []): BrowserServerInterface;

    /**
     * Returns the browser name.
     *
     * @return 'chromium'|'firefox'|'webkit'
     */
    public function name(): string;
}
