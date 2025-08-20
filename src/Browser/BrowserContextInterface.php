<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Browser;

use PlaywrightPHP\Network\NetworkThrottling;
use PlaywrightPHP\Page\PageInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface BrowserContextInterface
{
    /**
     * @param array<array{name: string, value: string, url?: string, domain?: string, path?: string, expires?: int, httpOnly?: bool, secure?: bool, sameSite?: 'Strict'|'Lax'|'None'}> $cookies
     */
    public function addCookies(array $cookies): void;

    public function addInitScript(string $script): void;

    public function clearCookies(): void;

    public function clearPermissions(): void;

    public function close(): void;

    /**
     * @param array<string>|null $urls
     *
     * @return array<array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: 'Strict'|'Lax'|'None'}>
     */
    public function cookies(?array $urls = null): array;

    public function exposeBinding(string $name, callable $callback): void;

    public function exposeFunction(string $name, callable $callback): void;

    /**
     * @param array<string> $permissions
     */
    public function grantPermissions(array $permissions): void;

    /**
     * @param array<string, mixed> $options
     */
    public function newPage(array $options = []): PageInterface;

    /**
     * @return array<PageInterface>
     */
    public function pages(): array;

    /**
     * Get storage state as array (legacy method).
     */
    public function storageState(?string $path = null): array;

    /**
     * Get storage state as StorageState object.
     */
    public function getStorageState(): StorageState;

    /**
     * Load storage state from StorageState object.
     */
    public function setStorageState(StorageState $storageState): void;

    /**
     * Save storage state to file.
     */
    public function saveStorageState(string $filePath): void;

    /**
     * Load storage state from file.
     */
    public function loadStorageState(string $filePath): void;

    public function route(string $url, callable $handler): void;

    public function unroute(string $url, ?callable $handler = null): void;

    public function getEnv(string $name): ?string;

    public function startTracing(PageInterface $page, array $options = []): void;

    public function stopTracing(PageInterface $page, string $path): void;

    /**
     * Set network throttling configuration.
     */
    public function setNetworkThrottling(NetworkThrottling $throttling): void;

    /**
     * Disable network throttling.
     */
    public function disableNetworkThrottling(): void;
}
