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

namespace Playwright\API;

/**
 * This context can be used to trigger API endpoints, configure micro-services,
 * prepare environment or the service to your e2e test.
 *
 * @see https://playwright.dev/docs/api/class-apirequestcontext
 */
interface APIRequestContextInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function get(string $url, array $options = []): APIResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $url, array $options = []): APIResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function put(string $url, array $options = []): APIResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function patch(string $url, array $options = []): APIResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function delete(string $url, array $options = []): APIResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function head(string $url, array $options = []): APIResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function fetch(string $urlOrRequest, array $options = []): APIResponseInterface;

    /**
     * @return array<array<string, mixed>>
     */
    public function storageState(?string $path = null): array;

    public function dispose(): void;
}
