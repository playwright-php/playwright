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
     * @param array<array{origin: string, localStorage: array<array{name: string, value: string}>}>                                                                $origins
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

        return new self(
            cookies: $data['cookies'] ?? [],
            origins: $data['origins'] ?? [],
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
     * Create a StorageState from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cookies: $data['cookies'] ?? [],
            origins: $data['origins'] ?? [],
        );
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(int $flags = 0): string
    {
        return \json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'cookies' => $this->cookies,
            'origins' => $this->origins,
        ];
    }

    /**
     * Save to JSON file.
     */
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

    /**
     * Check if storage state has any data.
     */
    public function isEmpty(): bool
    {
        return empty($this->cookies) && empty($this->origins);
    }

    /**
     * Get cookie count.
     */
    public function getCookieCount(): int
    {
        return \count($this->cookies);
    }

    /**
     * Get origin count.
     */
    public function getOriginCount(): int
    {
        return \count($this->origins);
    }

    /**
     * Get cookies for a specific domain.
     */
    public function getCookiesForDomain(string $domain): array
    {
        return \array_filter($this->cookies, fn (array $cookie) => $cookie['domain'] === $domain);
    }

    /**
     * Get localStorage data for a specific origin.
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
}
