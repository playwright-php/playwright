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

namespace Playwright\Testing;

use Playwright\Assertions\Options\ToHaveScreenshotOptions;

interface ExpectInterface
{
    public function toBeAttached(): void;

    public function toBeVisible(): void;

    public function toBeHidden(): void;

    public function toHaveText(string $text): void;

    public function toContainText(string $text): void;

    public function toHaveExactText(string $text): void;

    public function toHaveValue(string $value): void;

    public function toHaveAttribute(string $name, string $value): void;

    public function toBeChecked(): void;

    public function toBeEnabled(): void;

    public function toBeDisabled(): void;

    public function toHaveCSS(string $name, string $value): void;

    public function toHaveId(string $id): void;

    /**
     * Asserts the element's class attribute matches the expected list exactly.
     *
     * Order matters and extra classes fail the assertion. Use an array or a
     * space-separated string interchangeably; surrounding and repeated
     * whitespace is ignored on both sides.
     *
     * @param string|string[] $class
     */
    public function toHaveClass(string|array $class): void;

    public function toBeEmpty(): void;

    public function toHaveCount(int $count): void;

    public function toBeFocused(): void;

    public function toHaveFocus(): void;

    public function toHaveTitle(string $title): void;

    public function toHaveURL(string $url): void;

    /**
     * Compares a stable screenshot with a baseline image.
     *
     * A relative $name resolves in the snapshot directory of the test, suffixed
     * with the browser and the platform read from the page under test:
     * "card.png" in tests/CardTest.php, run in Chromium on Linux, is stored as
     * tests/CardTest.php-snapshots/card-chromium-linux.png. Without extension,
     * ".png" is added. Name it ".webp" for a lossless WebP baseline.
     */
    public function toHaveScreenshot(string $name, ?ToHaveScreenshotOptions $options = null): void;

    public function not(): self;

    public function withTimeout(int $timeoutMs): self;

    public function withPollInterval(int $pollIntervalMs): self;
}
