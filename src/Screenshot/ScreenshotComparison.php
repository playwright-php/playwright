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

namespace Playwright\Screenshot;

/**
 * Outcome of one stable screenshot compared against an expected image.
 * Binary fields hold raw PNG bytes.
 *
 * @internal
 */
final readonly class ScreenshotComparison
{
    public function __construct(
        public ?string $actual,
        public ?string $diff = null,
        public ?string $previous = null,
        public ?string $errorMessage = null,
        public bool $timedOut = false,
    ) {
    }

    /**
     * @param array<string, mixed> $response
     */
    public static function fromResponse(array $response): self
    {
        $binary = static function (mixed $value): ?string {
            if (!is_string($value) || '' === $value) {
                return null;
            }

            $decoded = base64_decode($value, true);

            return false === $decoded ? null : $decoded;
        };

        $errorMessage = $response['errorMessage'] ?? null;

        return new self(
            actual: $binary($response['actual'] ?? null),
            diff: $binary($response['diff'] ?? null),
            previous: $binary($response['previous'] ?? null),
            errorMessage: is_string($errorMessage) && '' !== $errorMessage ? $errorMessage : null,
            timedOut: true === ($response['timedOut'] ?? false),
        );
    }

    public function matches(): bool
    {
        return null === $this->errorMessage;
    }
}
