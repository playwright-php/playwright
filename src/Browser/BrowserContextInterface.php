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
use Playwright\Clock\ClockInterface;
use Playwright\Credentials\CredentialsInterface;
use Playwright\Network\NetworkThrottling;
use Playwright\Page\PageInterface;
use Playwright\Tracing\TracingInterface;

interface BrowserContextInterface
{
    /**
     * The context's clock, to fake and advance time.
     */
    public function clock(): ClockInterface;

    /**
     * The context's virtual WebAuthn authenticator, to seed and read passkeys.
     */
    public function credentials(): CredentialsInterface;

    /**
     * Sets the context's geolocation; null coordinates clear it.
     */
    public function setGeolocation(?float $latitude, ?float $longitude, ?float $accuracy = 0): void;

    /**
     * Toggles the context's network offline or back online.
     */
    public function setOffline(bool $offline): void;

    /**
     * @param array<array{name: string, value: string, url?: string, domain?: string, path?: string, expires?: int, httpOnly?: bool, secure?: bool, sameSite?: 'Strict'|'Lax'|'None'}> $cookies
     */
    public function addCookies(array $cookies): void;

    public function addInitScript(string $script): void;

    /**
     * Set extra HTTP headers for every request made by pages in this context.
     *
     * @param array<string, string> $headers
     */
    public function setExtraHTTPHeaders(array $headers): void;

    /**
     * @param array<array{domain: string, name: string, path: string}> $options
     */
    public function clearCookies(array $options = []): void;

    /**
     * Delete all cookies with the given name across domain and path variants.
     */
    public function deleteCookie(string $name): void;

    public function clearPermissions(): void;

    public function close(): void;

    /**
     * Whether the context is closed, including when its browser was closed instead.
     */
    public function isClosed(): bool;

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
     * The browser this context belongs to, or null for a persistent context.
     *
     * Contexts obtained from Browser::context() or Browser::newContext() always
     * carry their browser.
     */
    public function browser(): ?BrowserInterface;

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

    /**
     * Removes every route registered on this context in one call.
     *
     * 'behavior' decides what happens to handlers still running: wait for them,
     * wait but swallow their errors, or return without waiting.
     *
     * @param array{behavior?: 'default'|'wait'|'ignoreErrors'} $options
     */
    public function unrouteAll(array $options = []): void;

    public function getEnv(string $name): ?string;

    /**
     * Tracing helper associated with this context.
     */
    public function tracing(): TracingInterface;

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

    /**
     * Sets the default maximum time for methods that accept a timeout option.
     */
    public function setDefaultTimeout(int $timeout): void;

    /**
     * Sets the default maximum time for navigation methods.
     */
    public function setDefaultNavigationTimeout(int $timeout): void;
}
