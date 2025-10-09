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

use Playwright\API\APIRequestContextInterface;
use Playwright\Network\NetworkThrottling;
use Playwright\Page\PageInterface;

interface BrowserContextInterface
{
    /**
     * @param array<array{name: string, value: string, url?: string, domain?: string, path?: string, expires?: int, httpOnly?: bool, secure?: bool, sameSite?: 'Strict'|'Lax'|'None'}> $cookies
     */
    public function addCookies(array $cookies): void;

    public function addInitScript(string $script): void;

    public function clearCookies(): void;

    /**
     * Delete all cookies with the given name across domain and path variants.
     */
    public function deleteCookie(string $name): void;

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
     *
     * @return array<string, mixed>
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

    /**
     * @param array<string, mixed> $options
     */
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

    /**
     * @return array<string, mixed>
     */
    public function waitForEvent(string $event, ?callable $predicate = null, ?int $timeout = null): array;

    /**
     * @param array<string, mixed> $options
     */
    public function waitForPopup(callable $action, array $options = []): PageInterface;

    /**
     * API testing helper associated with this context.
     *
     * Requests made with this API will use context cookies.
     */
    public function request(): APIRequestContextInterface;
}
