<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Browser;

/**
 * Storage state container for cookies and localStorage data.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class StorageState
{
    /**
     * @param array<array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: 'Strict'|'Lax'|'None'}> $cookies
     * @param array<array{origin: string, localStorage?: array<array{name: string, value: string}>}>                                                               $origins
     */
    public function __construct(
        public array $cookies = [],
        public array $origins = [],
    ) {
    }

    /**
     * Create a StorageState from a JSON string.
     */
    public static function fromJson(string $json): self
    {
        $data = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON data for storage state');
        }

        $cookies = $data['cookies'] ?? [];
        $origins = $data['origins'] ?? [];

        if (!is_array($cookies)) {
            throw new \InvalidArgumentException('Invalid cookies data in storage state');
        }
        if (!is_array($origins)) {
            throw new \InvalidArgumentException('Invalid origins data in storage state');
        }

        return new self(
            cookies: self::validateCookies($cookies),
            origins: self::validateOrigins($origins),
        );
    }

    /**
     * Load StorageState from a JSON file.
     */
    public static function fromFile(string $filePath): self
    {
        if (!\file_exists($filePath)) {
            throw new \InvalidArgumentException(\sprintf('Storage state file not found: %s', $filePath));
        }

        $content = \file_get_contents($filePath);
        if (false === $content) {
            throw new \RuntimeException(\sprintf('Failed to read storage state file: %s', $filePath));
        }

        return self::fromJson($content);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $cookies = $data['cookies'] ?? [];
        $origins = $data['origins'] ?? [];

        if (!is_array($cookies)) {
            throw new \InvalidArgumentException('Invalid cookies data in storage state array');
        }
        if (!is_array($origins)) {
            throw new \InvalidArgumentException('Invalid origins data in storage state array');
        }

        return new self(
            cookies: self::validateCookies($cookies),
            origins: self::validateOrigins($origins),
        );
    }

    public function toJson(int $flags = 0): string
    {
        return \json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cookies' => $this->cookies,
            'origins' => $this->origins,
        ];
    }

    public function saveToFile(string $filePath): void
    {
        $directory = \dirname($filePath);
        if (!\is_dir($directory) && !\mkdir($directory, 0755, true) && !\is_dir($directory)) {
            throw new \RuntimeException(\sprintf('Failed to create directory: %s', $directory));
        }

        $result = \file_put_contents($filePath, $this->toJson(JSON_PRETTY_PRINT));
        if (false === $result) {
            throw new \RuntimeException(\sprintf('Failed to write storage state file: %s', $filePath));
        }
    }

    public function isEmpty(): bool
    {
        return empty($this->cookies) && empty($this->origins);
    }

    public function getCookieCount(): int
    {
        return \count($this->cookies);
    }

    public function getOriginCount(): int
    {
        return \count($this->origins);
    }

    /**
     * @return array<array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: 'Strict'|'Lax'|'None'}>
     */
    public function getCookiesForDomain(string $domain): array
    {
        return \array_filter($this->cookies, fn (array $cookie) => $cookie['domain'] === $domain);
    }

    /**
     * @return array<array{name: string, value: string}>
     */
    public function getLocalStorageForOrigin(string $origin): array
    {
        foreach ($this->origins as $originData) {
            if ($originData['origin'] === $origin) {
                return $originData['localStorage'] ?? [];
            }
        }

        return [];
    }

    /**
     * @param array<mixed, mixed> $cookies
     *
     * @return array<array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: 'Lax'|'None'|'Strict'}>
     */
    private static function validateCookies(array $cookies): array
    {
        // Basic validation - in production you might want more strict validation
        foreach ($cookies as $cookie) {
            if (!is_array($cookie)) {
                throw new \InvalidArgumentException('Invalid cookie data structure');
            }
            // Add specific field validation if needed
        }

        /* @var array<array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: 'Lax'|'None'|'Strict'}> $cookies */
        return $cookies;
    }

    /**
     * @param array<mixed, mixed> $origins
     *
     * @return array<array{origin: string, localStorage?: array<array{name: string, value: string}>}>
     */
    private static function validateOrigins(array $origins): array
    {
        // Basic validation - in production you might want more strict validation
        foreach ($origins as $origin) {
            if (!is_array($origin)) {
                throw new \InvalidArgumentException('Invalid origin data structure');
            }
        }

        /* @var array<array{origin: string, localStorage?: array<array{name: string, value: string}>}> $origins */
        return $origins;
    }
}
