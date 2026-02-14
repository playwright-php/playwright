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

namespace Playwright;

/**
 * @internal
 */
final class Cookie
{
    /**
     * @param 'Strict'|'Lax'|'None' $sameSite
     */
    private function __construct(
        public string $name,
        public string $value,
        public string $domain,
        public string $path,
        public int|float $expires,
        public bool $httpOnly,
        public bool $secure,
        public string $sameSite,
    ) {
    }

    /**
     * @param array<mixed, mixed> $cookie
     */
    public static function fromArray(array $cookie): self
    {
        $sameSite = $cookie['sameSite'] ?? null;

        if (!is_string($cookie['name'] ?? null)
            || !is_string($cookie['value'] ?? null)
            || !is_string($cookie['domain'] ?? null)
            || !is_string($cookie['path'] ?? null)
            || (!is_int($cookie['expires'] ?? null) && !is_float($cookie['expires'] ?? null))
            || !is_bool($cookie['httpOnly'] ?? null)
            || !is_bool($cookie['secure'] ?? null)
            || !in_array($sameSite, ['Lax', 'None', 'Strict'], true)
        ) {
            throw new \InvalidArgumentException('Invalid cookie fields');
        }

        return new self(
            name: $cookie['name'],
            value: $cookie['value'],
            domain: $cookie['domain'],
            path: $cookie['path'],
            expires: $cookie['expires'],
            httpOnly: $cookie['httpOnly'],
            secure: $cookie['secure'],
            sameSite: $sameSite,
        );
    }

    /**
     * @return array{name: string, value: string, domain: string, path: string, expires: int|float, httpOnly: bool, secure: bool, sameSite: 'Strict'|'Lax'|'None'}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'domain' => $this->domain,
            'path' => $this->path,
            'expires' => $this->expires,
            'httpOnly' => $this->httpOnly,
            'secure' => $this->secure,
            'sameSite' => $this->sameSite,
        ];
    }
}
