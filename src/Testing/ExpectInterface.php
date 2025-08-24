<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Testing;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
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

    public function toHaveCount(int $count): void;

    public function toBeFocused(): void;

    
    public function toHaveTitle(string $title): void;

    public function toHaveURL(string $url): void;

    
    public function not(): self;

    public function withTimeout(int $timeoutMs): self;

    public function withPollInterval(int $pollIntervalMs): self;
}
