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

interface ExpectInterface
{
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
     * @param string|string[] $class
     */
    public function toHaveClass(string|array $class): void;

    public function toBeEmpty(): void;

    public function toHaveCount(int $count): void;

    public function toBeFocused(): void;

    public function toHaveFocus(): void;

    public function toHaveTitle(string $title): void;

    public function toHaveURL(string $url): void;

    public function not(): self;

    public function withTimeout(int $timeoutMs): self;

    public function withPollInterval(int $pollIntervalMs): self;
}
