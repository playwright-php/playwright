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

use Playwright\Assertions\LocatorAssertions;
use Playwright\Assertions\LocatorAssertionsInterface;
use Playwright\Assertions\PageAssertions;
use Playwright\Assertions\PageAssertionsInterface;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;
use Playwright\Tracing\TracingInterface;

final class Expect implements ExpectInterface
{
    private readonly LocatorAssertionsInterface|PageAssertionsInterface $assertions;

    public function __construct(LocatorInterface|PageInterface $subject, ?TracingInterface $tracing = null)
    {
        $this->assertions = $subject instanceof LocatorInterface
            ? new LocatorAssertions($subject, $tracing)
            : new PageAssertions($subject, $tracing);
        $this->assertions->withPollInterval(100);
    }

    public function not(): self
    {
        $this->assertions->not();

        return $this;
    }

    public function withTimeout(int $timeoutMs): self
    {
        $this->assertions->withTimeout($timeoutMs);

        return $this;
    }

    public function withPollInterval(int $pollIntervalMs): self
    {
        $this->assertions->withPollInterval($pollIntervalMs);

        return $this;
    }

    public function toBeAttached(): void
    {
        $this->locator(__FUNCTION__)->toBeAttached();
    }

    public function toBeVisible(): void
    {
        $this->locator(__FUNCTION__)->toBeVisible();
    }

    public function toBeHidden(): void
    {
        $this->locator(__FUNCTION__)->toBeHidden();
    }

    public function toHaveText(string $text): void
    {
        $this->locator(__FUNCTION__)->toContainText($text);
    }

    public function toContainText(string $text): void
    {
        $this->locator(__FUNCTION__)->toContainText($text);
    }

    public function toHaveExactText(string $text): void
    {
        $this->locator(__FUNCTION__)->toHaveExactText($text);
    }

    public function toHaveValue(string $value): void
    {
        $this->locator(__FUNCTION__)->toHaveValue($value);
    }

    public function toHaveAttribute(string $name, string $value): void
    {
        $this->locator(__FUNCTION__)->toHaveAttribute($name, $value);
    }

    public function toBeChecked(): void
    {
        $this->locator(__FUNCTION__)->toBeChecked();
    }

    public function toBeEnabled(): void
    {
        $this->locator(__FUNCTION__)->toBeEnabled();
    }

    public function toBeDisabled(): void
    {
        $this->locator(__FUNCTION__)->toBeDisabled();
    }

    public function toHaveCSS(string $name, string $value): void
    {
        $this->locator(__FUNCTION__)->toHaveCSS($name, $value);
    }

    public function toHaveId(string $id): void
    {
        $this->locator(__FUNCTION__)->toHaveId($id);
    }

    /**
     * @param string|string[] $class
     */
    public function toHaveClass(string|array $class): void
    {
        $this->locator(__FUNCTION__)->toHaveClass($class);
    }

    public function toBeEmpty(): void
    {
        $this->locator(__FUNCTION__)->toBeEmpty();
    }

    public function toHaveCount(int $count): void
    {
        $this->locator(__FUNCTION__)->toHaveCount($count);
    }

    public function toBeFocused(): void
    {
        $this->locator(__FUNCTION__)->toBeFocused();
    }

    public function toHaveFocus(): void
    {
        $this->locator(__FUNCTION__)->toHaveFocus();
    }

    public function toHaveTitle(string $title): void
    {
        $this->page(__FUNCTION__)->toHaveTitle($title);
    }

    public function toHaveURL(string $url): void
    {
        $this->page(__FUNCTION__)->toHaveURL($url);
    }

    private function locator(string $matcher): LocatorAssertionsInterface
    {
        if (!$this->assertions instanceof LocatorAssertionsInterface) {
            throw new \InvalidArgumentException(sprintf('%s() can only be used with LocatorInterface', $matcher));
        }

        return $this->assertions;
    }

    private function page(string $matcher): PageAssertionsInterface
    {
        if (!$this->assertions instanceof PageAssertionsInterface) {
            throw new \InvalidArgumentException(sprintf('%s() can only be used with PageInterface', $matcher));
        }

        return $this->assertions;
    }
}
