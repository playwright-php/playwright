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
use Playwright\Assertions\Options\ToHaveScreenshotOptions;
use Playwright\Assertions\PageAssertions;
use Playwright\Assertions\PageAssertionsInterface;
use Playwright\Exception\RuntimeException;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;
use Playwright\Tracing\TracingInterface;

final class Expect implements ExpectInterface
{
    private readonly LocatorAssertionsInterface|PageAssertionsInterface $assertions;

    /**
     * Browser and platform of the run, "chromium-linux": the suffix of relative
     * baseline names, as {projectName}-{platform} in @playwright/test.
     */
    private readonly string $variant;

    /**
     * @param string|null $outputDir Directory of the test for failure images. The browser is appended, as Playwright JS
     *                               appends the project: each browser of a test gets its own directory.
     */
    public function __construct(
        LocatorInterface|PageInterface $subject,
        ?TracingInterface $tracing = null,
        private readonly ?string $snapshotDir = null,
        ?string $outputDir = null,
    ) {
        $browser = self::browserName($subject);
        $this->variant = implode('-', array_filter([$browser, self::platform()]));

        if (null !== $outputDir && '' !== $browser) {
            $outputDir .= '-'.$browser;
        }

        $this->assertions = $subject instanceof LocatorInterface
            ? new LocatorAssertions($subject, $tracing, $outputDir, $this->variant)
            : new PageAssertions($subject, $tracing, $outputDir, $this->variant);
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

    public function toHaveScreenshot(string $name, ?ToHaveScreenshotOptions $options = null): void
    {
        $this->assertions->toHaveScreenshot($this->snapshotPath($name), $options);
    }

    private function snapshotPath(string $name): string
    {
        if (str_starts_with($name, '/') || str_starts_with($name, '\\\\') || 1 === preg_match('~^[a-zA-Z]:[\\\\/]~', $name)) {
            return $name;
        }

        $extension = pathinfo($name, \PATHINFO_EXTENSION);
        $stem = '' === $extension ? $name : substr($name, 0, -\strlen($extension) - 1);

        return sprintf('%s/%s-%s.%s', $this->snapshotDir ?? getcwd(), $stem, $this->variant, '' === $extension ? 'png' : $extension);
    }

    /**
     * The browser rendering the subject, read from the page under test.
     * Empty when it cannot be known: a persistent context has no browser
     * object, a locator built by hand has no page.
     */
    private static function browserName(LocatorInterface|PageInterface $subject): string
    {
        try {
            $page = $subject instanceof LocatorInterface ? $subject->page() : $subject;

            return $page->context()->browser()?->browserType()->value ?? '';
        } catch (RuntimeException) {
            return '';
        }
    }

    private static function platform(): string
    {
        return 'Windows' === \PHP_OS_FAMILY ? 'win32' : strtolower(\PHP_OS_FAMILY);
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
