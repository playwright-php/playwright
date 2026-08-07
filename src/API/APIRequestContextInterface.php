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

use Playwright\Tracing\TracingInterface;

/**
 * Executes HTTP requests through Playwright.
 *
 * Use a context to prepare application state without driving a browser page.
 * Request options apply to a single call unless configured when it is created.
 *
 * @see https://playwright.dev/docs/api/class-apirequestcontext
 */
interface APIRequestContextInterface
{
    /**
     * Sends a GET request.
     *
     * Resolves relative URLs against the configured base URL.
     * Returns a response object even for unsuccessful HTTP status codes.
     *
     * @param array<string, mixed> $options
     */
    public function get(string $url, array $options = []): APIResponseInterface;

    /**
     * Sends a POST request.
     *
     * Request options can provide data, form fields, headers, and timeouts.
     * Returns a response object even for unsuccessful HTTP status codes.
     *
     * @param array<string, mixed> $options
     */
    public function post(string $url, array $options = []): APIResponseInterface;

    /**
     * Sends a PUT request.
     *
     * Request options can provide data, form fields, headers, and timeouts.
     * Returns a response object even for unsuccessful HTTP status codes.
     *
     * @param array<string, mixed> $options
     */
    public function put(string $url, array $options = []): APIResponseInterface;

    /**
     * Sends a PATCH request.
     *
     * Request options can provide data, form fields, headers, and timeouts.
     * Returns a response object even for unsuccessful HTTP status codes.
     *
     * @param array<string, mixed> $options
     */
    public function patch(string $url, array $options = []): APIResponseInterface;

    /**
     * Sends a DELETE request.
     *
     * Request options can provide headers, query parameters, and timeouts.
     * Returns a response object even for unsuccessful HTTP status codes.
     *
     * @param array<string, mixed> $options
     */
    public function delete(string $url, array $options = []): APIResponseInterface;

    /**
     * Sends a HEAD request.
     *
     * Request options can provide headers, query parameters, and timeouts.
     * Returns a response object without downloading a response body.
     *
     * @param array<string, mixed> $options
     */
    public function head(string $url, array $options = []): APIResponseInterface;

    /**
     * Sends an HTTP request.
     *
     * The method, headers, body, and retry behavior are provided in options.
     * Relative URLs resolve against the base URL configured for this context.
     *
     * @param array<string, mixed> $options
     */
    public function fetch(string $urlOrRequest, array $options = []): APIResponseInterface;

    /**
     * Returns the context storage state.
     *
     * The returned cookies and origins can initialize another API or browser context.
     * Pass a path to save the serialized state for later reuse.
     *
     * @return array<array<string, mixed>>
     */
    public function storageState(?string $path = null): array;

    /**
     * Tracing controls for this context. A context obtained from a browser context traces into that
     * same browser context.
     */
    public function tracing(): TracingInterface;

    /**
     * Releases the request context.
     *
     * Cancels pending work owned by this context on the Playwright server.
     * Do not use the context for further requests after disposal.
     */
    public function dispose(): void;
}
