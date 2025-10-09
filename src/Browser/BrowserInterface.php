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

use Playwright\Page\PageInterface;

interface BrowserInterface
{
    public function context(): BrowserContextInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function newContext(array $options = []): BrowserContextInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function newPage(array $options = []): PageInterface;

    public function close(): void;

    /**
     * @return array<BrowserContextInterface>
     */
    public function contexts(): array;

    public function isConnected(): bool;

    public function version(): string;
}
