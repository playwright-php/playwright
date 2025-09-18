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

use Playwright\Exception\RuntimeException;

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
            throw new RuntimeException(\sprintf('Failed to read storage state file: %s', $filePath));
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
            throw new RuntimeException(\sprintf('Failed to create directory: %s', $directory));
        }

        $result = \file_put_contents($filePath, $this->toJson(JSON_PRETTY_PRINT));
        if (false === $result) {
            throw new RuntimeException(\sprintf('Failed to write storage state file: %s', $filePath));
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
        $result = [];
        foreach ($cookies as $cookie) {
            if (!is_array($cookie)) {
                throw new \InvalidArgumentException('Invalid cookie data structure');
            }

            $name = $cookie['name'] ?? null;
            $value = $cookie['value'] ?? null;
            $domain = $cookie['domain'] ?? null;
            $path = $cookie['path'] ?? null;
            $expires = $cookie['expires'] ?? null;
            $httpOnly = $cookie['httpOnly'] ?? null;
            $secure = $cookie['secure'] ?? null;
            $sameSite = $cookie['sameSite'] ?? null;

            if (!is_string($name)
                || !is_string($value)
                || !is_string($domain)
                || !is_string($path)
                || !is_int($expires)
                || !is_bool($httpOnly)
                || !is_bool($secure)
                || !is_string($sameSite)
                || !in_array($sameSite, ['Lax', 'None', 'Strict'], true)
            ) {
                throw new \InvalidArgumentException('Invalid cookie fields');
            }

            $result[] = [
                'name' => $name,
                'value' => $value,
                'domain' => $domain,
                'path' => $path,
                'expires' => $expires,
                'httpOnly' => $httpOnly,
                'secure' => $secure,
                'sameSite' => $sameSite,
            ];
        }

        return $result;
    }

    /**
     * @param array<mixed, mixed> $origins
     *
     * @return array<array{origin: string, localStorage?: array<array{name: string, value: string}>}>
     */
    private static function validateOrigins(array $origins): array
    {
        $result = [];
        foreach ($origins as $origin) {
            if (!is_array($origin)) {
                throw new \InvalidArgumentException('Invalid origin data structure');
            }

            $originStr = $origin['origin'] ?? null;
            if (!is_string($originStr)) {
                throw new \InvalidArgumentException('Invalid origin field');
            }

            $localStorageItems = [];
            if (isset($origin['localStorage'])) {
                if (!is_array($origin['localStorage'])) {
                    throw new \InvalidArgumentException('Invalid localStorage type');
                }
                foreach ($origin['localStorage'] as $item) {
                    if (!is_array($item) || !is_string($item['name'] ?? null) || !is_string($item['value'] ?? null)) {
                        throw new \InvalidArgumentException('Invalid localStorage item');
                    }
                    $localStorageItems[] = ['name' => $item['name'], 'value' => $item['value']];
                }
            }

            $originEntry = ['origin' => $originStr];
            if ([] !== $localStorageItems) {
                $originEntry['localStorage'] = $localStorageItems;
            }

            $result[] = $originEntry;
        }

        return $result;
    }
}
