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

namespace Playwright\Network;

interface ResponseInterface
{
    public function url(): string;

    public function status(): int;

    public function statusText(): string;

    public function ok(): bool;

    /**
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * Case-insensitive single header value (first value if multiple), or null if absent.
     */
    public function headerValue(string $name): ?string;

    /**
     * Case-insensitive multiple header values (split on commas).
     *
     * @return array<string>
     */
    public function headerValues(string $name): array;

    /**
     * Headers as a list of name/value pairs; values split on commas.
     *
     * @return array<array{name: string, value: string}>
     */
    public function headersArray(): array;

    public function body(): string;

    public function text(): string;

    /**
     * @return array<string, mixed>
     */
    public function json(): array;
}
