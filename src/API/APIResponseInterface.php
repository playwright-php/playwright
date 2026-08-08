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
 * Represents an HTTP API response.
 *
 * Status, headers, and body data originate from an API request.
 * Response accessors do not perform additional network requests.
 */
interface APIResponseInterface
{
    /**
     * Reports whether the response succeeded.
     *
     * Status codes from 200 through 299 are considered successful.
     * Redirect and client or server errors return false.
     */
    public function ok(): bool;

    /**
     * Returns the HTTP status code.
     *
     * The value is the code received from the remote server.
     * Use ok() when only the success range matters.
     */
    public function status(): int;

    /**
     * Returns the HTTP status text.
     *
     * The value comes from the remote server response when available.
     * An empty string represents a missing status text.
     */
    public function statusText(): string;

    /**
     * Returns the final response URL.
     *
     * Redirects can make this differ from the request URL.
     * An empty string represents a missing URL in the protocol response.
     */
    public function url(): string;

    /**
     * Returns one value per response header.
     *
     * Repeated headers retain their first value in this representation.
     * Header names preserve the casing supplied by the server.
     *
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * Returns all values for every response header.
     *
     * Repeated headers remain separate values in the returned arrays.
     * Header names preserve the casing supplied by the server.
     *
     * @return array<string, string[]>
     */
    public function headersArray(): array;

    /**
     * Returns the first value for a response header.
     *
     * Header name matching is case-insensitive.
     * Returns null when the response does not contain the requested header.
     */
    public function headerValue(string $name): ?string;

    /**
     * Returns all values for a response header.
     *
     * Header name matching is case-insensitive.
     * The returned array is empty when the response does not contain the header.
     *
     * @return array<string, string[]>
     */
    public function headerValues(string $name): array;

    /**
     * Returns the response body as text.
     *
     * This is an alias for text() provided for API parity.
     * No content decoding is applied beyond the Playwright protocol response.
     */
    public function body(): string;

    /**
     * Decodes the response body as JSON.
     *
     * The top-level JSON value must be an object or array.
     * Invalid JSON raises a Playwright exception.
     *
     * @return array<string, mixed>
     */
    public function json(): array;

    /**
     * Returns the response body as text.
     *
     * The returned string is the body received through the Playwright protocol.
     * An empty string represents a missing body.
     */
    public function text(): string;

    /**
     * Releases response resources.
     *
     * Call this when a response implementation holds disposable resources.
     * The bundled implementation currently retains only in-memory response data.
     */
    public function dispose(): void;
}
