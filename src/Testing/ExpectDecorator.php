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

/**
 * Decorator for Expect that automatically increments PHPUnit assertion count when available.
 * This allows the Expect class to work independently (without PHPUnit) while still
 * counting assertions properly when used within PHPUnit tests.
 */
final class ExpectDecorator implements ExpectInterface
{
    private int $assertionCount = 0;

    public function __construct(
        private readonly ExpectInterface $expect,
        private readonly ?object $testCase = null,
    ) {
    }

    public function toBeVisible(): void
    {
        $this->expect->toBeVisible();
        $this->recordAssertion(1);
    }

    public function toBeHidden(): void
    {
        $this->expect->toBeHidden();
        $this->recordAssertion(1);
    }

    public function toHaveText(string $text): void
    {
        $this->expect->toHaveText($text);
        $this->recordAssertion(1);
    }

    public function toContainText(string $text): void
    {
        $this->expect->toContainText($text);
        $this->recordAssertion(1);
    }

    public function toHaveExactText(string $text): void
    {
        $this->expect->toHaveExactText($text);
        $this->recordAssertion(1);
    }

    public function toHaveValue(string $value): void
    {
        $this->expect->toHaveValue($value);
        $this->recordAssertion(1);
    }

    public function toHaveAttribute(string $name, string $value): void
    {
        $this->expect->toHaveAttribute($name, $value);
        $this->recordAssertion(1);
    }

    public function toBeChecked(): void
    {
        $this->expect->toBeChecked();
        $this->recordAssertion(1);
    }

    public function toBeEnabled(): void
    {
        $this->expect->toBeEnabled();
        $this->recordAssertion(1);
    }

    public function toBeDisabled(): void
    {
        $this->expect->toBeDisabled();
        $this->recordAssertion(1);
    }

    public function toHaveCSS(string $name, string $value): void
    {
        $this->expect->toHaveCSS($name, $value);
        $this->recordAssertion(1);
    }

    public function toHaveId(string $id): void
    {
        $this->expect->toHaveId($id);
        $this->recordAssertion(1);
    }

    /**
     * @param string|string[] $class
     */
    public function toHaveClass(string|array $class): void
    {
        $this->expect->toHaveClass($class);
        $this->recordAssertion(1);
    }

    public function toBeEmpty(): void
    {
        $this->expect->toBeEmpty();
        $this->recordAssertion(1);
    }

    public function toHaveCount(int $count): void
    {
        $this->expect->toHaveCount($count);
        $this->recordAssertion(1);
    }

    public function toBeFocused(): void
    {
        $this->expect->toBeFocused();
        $this->recordAssertion(1);
    }

    public function toHaveFocus(): void
    {
        $this->expect->toHaveFocus();
        $this->recordAssertion(1);
    }

    public function toHaveTitle(string $title): void
    {
        $this->expect->toHaveTitle($title);
        $this->recordAssertion(1);
    }

    public function toHaveURL(string $url): void
    {
        $this->expect->toHaveURL($url);
        $this->recordAssertion(1);
    }

    public function not(): self
    {
        $this->expect->not();

        return $this;
    }

    public function withTimeout(int $timeoutMs): self
    {
        $this->expect->withTimeout($timeoutMs);

        return $this;
    }

    public function withPollInterval(int $pollIntervalMs): self
    {
        $this->expect->withPollInterval($pollIntervalMs);

        return $this;
    }

    private function recordAssertion(int $count): void
    {
        $this->assertionCount += $count;

        if (null !== $this->testCase && method_exists($this->testCase, 'addToAssertionCount')) {
            $this->testCase->addToAssertionCount($count);
        }
    }
}
