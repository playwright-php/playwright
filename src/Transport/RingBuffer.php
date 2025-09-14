<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport;

/**
 * Fixed-size ring buffer for capturing recent stderr/stdout lines.
 * Useful for debugging failed processes by keeping the most recent output.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class RingBuffer
{
    /** @var list<string> */
    private array $buf = [];

    public function __construct(
        private readonly int $max = 200,
    ) {
        if ($max < 1) {
            throw new \InvalidArgumentException('RingBuffer size must be at least 1');
        }
    }

    /**
     * Add a line to the buffer, automatically trimming old lines if needed.
     */
    public function push(string $line): void
    {
        $this->buf[] = rtrim($line, "\r\n");
        $excess = \count($this->buf) - $this->max;
        if ($excess > 0) {
            $this->buf = \array_slice($this->buf, $excess);
        }
    }

    /**
     * Clear all lines from the buffer.
     */
    public function clear(): void
    {
        $this->buf = [];
    }

    /**
     * Get all lines as a single string.
     */
    public function toString(string $separator = PHP_EOL): string
    {
        return implode($separator, $this->buf);
    }

    /**
     * Get all lines as an array.
     *
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->buf;
    }

    /**
     * Get the number of lines in the buffer.
     */
    public function count(): int
    {
        return \count($this->buf);
    }

    /**
     * Check if the buffer is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->buf);
    }

    /**
     * Get the maximum capacity of the buffer.
     */
    public function getMaxSize(): int
    {
        return $this->max;
    }
}
