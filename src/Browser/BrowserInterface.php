<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Browser;

use PlaywrightPHP\Page\PageInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
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
