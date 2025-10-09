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

interface RequestInterface
{
    public function url(): string;

    public function method(): string;

    /**
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * Case-insensitive single header value, or null if not present.
     */
    public function headerValue(string $name): ?string;

    /**
     * Headers as a list of name/value pairs.
     *
     * @return array<array{name: string, value: string}>
     */
    public function headersArray(): array;

    /**
     * All headers as a map (alias of headers()).
     *
     * @return array<string, string>
     */
    public function allHeaders(): array;

    public function postData(): ?string;

    /**
     * @return array<string, mixed>|null
     */
    public function postDataJSON(): ?array;

    public function resourceType(): string;
}
