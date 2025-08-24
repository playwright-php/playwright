<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Network;

/**
 * Network throttling presets and configuration.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class NetworkThrottling
{
    public function __construct(
        public int $downloadThroughput,
        public int $uploadThroughput,
        public int $latency,
    ) {
    }

    /**
     * No throttling (default).
     */
    public static function none(): self
    {
        return new self(
            downloadThroughput: 0,
            uploadThroughput: 0,
            latency: 0,
        );
    }

    /**
     * Slow 3G connection.
     */
    public static function slow3G(): self
    {
        return new self(
            downloadThroughput: 50 * 1024,
            uploadThroughput: 50 * 1024,
            latency: 2000,
        );
    }

    /**
     * Fast 3G connection.
     */
    public static function fast3G(): self
    {
        return new self(
            downloadThroughput: 150 * 1024,
            uploadThroughput: 75 * 1024,
            latency: 562,
        );
    }

    /**
     * 4G connection.
     */
    public static function fast4G(): self
    {
        return new self(
            downloadThroughput: (int) (1.6 * 1024 * 1024),
            uploadThroughput: 750 * 1024,
            latency: 150,
        );
    }

    /**
     * DSL connection.
     */
    public static function dsl(): self
    {
        return new self(
            downloadThroughput: 2 * 1024 * 1024,
            uploadThroughput: 1 * 1024 * 1024,
            latency: 5,
        );
    }

    /**
     * WiFi connection.
     */
    public static function wifi(): self
    {
        return new self(
            downloadThroughput: 30 * 1024 * 1024,
            uploadThroughput: 15 * 1024 * 1024,
            latency: 2,
        );
    }

    /**
     * Create custom throttling configuration.
     */
    public static function custom(int $downloadThroughput, int $uploadThroughput, int $latency): self
    {
        return new self($downloadThroughput, $uploadThroughput, $latency);
    }

    /**
     * Convert to array for transport.
     *
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'downloadThroughput' => $this->downloadThroughput,
            'uploadThroughput' => $this->uploadThroughput,
            'latency' => $this->latency,
        ];
    }

    /**
     * Check if throttling is disabled.
     */
    public function isDisabled(): bool
    {
        return 0 === $this->downloadThroughput
               && 0 === $this->uploadThroughput
               && 0 === $this->latency;
    }

    /**
     * Get human-readable description.
     */
    public function getDescription(): string
    {
        if ($this->isDisabled()) {
            return 'No throttling';
        }

        $download = $this->formatThroughput($this->downloadThroughput);
        $upload = $this->formatThroughput($this->uploadThroughput);

        return \sprintf('Download: %s, Upload: %s, Latency: %dms', $download, $upload, $this->latency);
    }

    /**
     * Format throughput for display.
     */
    private function formatThroughput(int $throughput): string
    {
        if ($throughput > 780000) {
            return \sprintf('%.1f MB/s', $throughput / (1024 * 1024));
        }

        if ($throughput >= 1024) {
            return \sprintf('%.0f KB/s', $throughput / 1024);
        }

        return \sprintf('%d B/s', $throughput);
    }
}
