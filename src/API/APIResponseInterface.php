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
 * API response from HTTP requests.
 */
interface APIResponseInterface
{
    public function ok(): bool;

    public function status(): int;

    public function statusText(): string;

    public function url(): string;

    /**
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * @return array<string, string[]>
     */
    public function headersArray(): array;

    public function headerValue(string $name): ?string;

    /**
     * @return array<string, string[]>
     */
    public function headerValues(string $name): array;

    public function body(): string;

    /**
     * @return array<string, mixed>
     */
    public function json(): array;

    public function text(): string;

    public function dispose(): void;
}
