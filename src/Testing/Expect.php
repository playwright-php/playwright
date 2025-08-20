<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Testing;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PlaywrightPHP\Locator\LocatorInterface;
use PlaywrightPHP\Page\PageInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class Expect implements ExpectInterface
{
    private bool $negated = false;

    private int $timeoutMs = 5000;

    private int $pollIntervalMs = 100;

    public function __construct(
        private readonly LocatorInterface|PageInterface $subject,
    ) {
    }

    public function not(): self
    {
        $this->negated = !$this->negated;

        return $this;
    }

    public function withTimeout(int $timeoutMs): self
    {
        $this->timeoutMs = $timeoutMs;

        return $this;
    }

    public function withPollInterval(int $pollIntervalMs): self
    {
        $this->pollIntervalMs = $pollIntervalMs;

        return $this;
    }

    public function toBeVisible(): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toBeVisible() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => $this->subject->isVisible(),
            !$this->negated,
            $this->negated ? 'Locator is visible, but expected not to be.' : 'Locator is not visible.'
        );
    }

    public function toHaveText(string $text): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toHaveText() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => \str_contains($this->subject->textContent() ?? '', $text),
            !$this->negated,
            $this->negated
                ? \sprintf('Locator text contains "%s", but expected not to.', $text)
                : \sprintf('Locator text does not contain "%s".', $text),
            function () use ($text): string {
                $actual = (string) ($this->subject->textContent() ?? '');

                return $this->negated
                    ? \sprintf('Expected text not to contain %s. Actual: %s', \json_encode($text), \json_encode($actual))
                    : \sprintf('Expected text to contain %s, but was %s', \json_encode($text), \json_encode($actual));
            }
        );
    }

    public function toContainText(string $text): void
    {
        // Alias of toHaveText (both check substring containment)
        $this->toHaveText($text);
    }

    public function toHaveExactText(string $text): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toHaveExactText() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => (string) ($this->subject->textContent() ?? '') === $text,
            !$this->negated,
            $this->negated
                ? \sprintf('Locator text is exactly "%s", but expected not to be.', $text)
                : \sprintf('Locator text is not exactly "%s".', $text),
            function () use ($text): string {
                $actual = (string) ($this->subject->textContent() ?? '');

                return $this->negated
                    ? \sprintf('Expected exact text not %s. Actual: %s', \json_encode($text), \json_encode($actual))
                    : \sprintf('Expected exact text %s, but was %s', \json_encode($text), \json_encode($actual));
            }
        );
    }

    public function toHaveValue(string $value): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toHaveValue() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => $this->subject->inputValue() === $value,
            !$this->negated,
            $this->negated
                ? \sprintf('Locator value is "%s", but expected not to be.', $value)
                : \sprintf('Locator value is not "%s".', $value),
            function () use ($value): string {
                $actual = (string) $this->subject->inputValue();

                return $this->negated
                    ? \sprintf('Expected value not to be %s. Actual: %s', \json_encode($value), \json_encode($actual))
                    : \sprintf('Expected value %s, but was %s', \json_encode($value), \json_encode($actual));
            }
        );
    }

    public function toHaveAttribute(string $name, string $value): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toHaveAttribute() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => $this->subject->getAttribute($name) === $value,
            !$this->negated,
            $this->negated
                ? \sprintf('Locator attribute "%s" is "%s", but expected not to be.', $name, $value)
                : \sprintf('Locator attribute "%s" is not "%s".', $name, $value),
            function () use ($name, $value): string {
                $actual = $this->subject->getAttribute($name);

                return $this->negated
                    ? \sprintf('Expected attribute %s not to be %s. Actual: %s', \json_encode($name), \json_encode($value), \json_encode($actual))
                    : \sprintf('Expected attribute %s = %s, but was %s', \json_encode($name), \json_encode($value), \json_encode($actual));
            }
        );
    }

    public function toBeChecked(): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toBeChecked() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => $this->subject->isChecked(),
            !$this->negated,
            $this->negated ? 'Locator is checked, but expected not to be.' : 'Locator is not checked.'
        );
    }

    public function toBeEnabled(): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toBeEnabled() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => $this->subject->isEnabled(),
            !$this->negated,
            $this->negated ? 'Locator is enabled, but expected not to be.' : 'Locator is not enabled.'
        );
    }

    public function toBeHidden(): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toBeHidden() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => !$this->subject->isVisible(),
            !$this->negated,
            $this->negated ? 'Locator is hidden, but expected not to be.' : 'Locator is not hidden.'
        );
    }

    public function toBeDisabled(): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toBeDisabled() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => !$this->subject->isEnabled(),
            !$this->negated,
            $this->negated ? 'Locator is disabled, but expected not to be.' : 'Locator is not disabled.'
        );
    }

    public function toHaveCount(int $count): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toHaveCount() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => $this->subject->count() === $count,
            !$this->negated,
            $this->negated
                ? \sprintf('Locator count is %d, but expected not to be.', $count)
                : \sprintf('Locator count is not %d.', $count),
            function () use ($count): string {
                $actual = $this->subject->count();

                return $this->negated
                    ? \sprintf('Expected count not %d. Actual: %d', $count, $actual)
                    : \sprintf('Expected count %d, but was %d', $count, $actual);
            }
        );
    }

    public function toBeFocused(): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toBeFocused() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => (bool) $this->subject->evaluate('(element) => document.activeElement === element'),
            !$this->negated,
            $this->negated ? 'Locator is focused, but expected not to be.' : 'Locator is not focused.'
        );
    }

    public function toHaveTitle(string $title): void
    {
        if (!$this->subject instanceof PageInterface) {
            throw new \InvalidArgumentException('toHaveTitle() can only be used with PageInterface');
        }

        $this->retryAssertion(
            fn () => $this->subject->title() === $title,
            !$this->negated,
            $this->negated
                ? \sprintf('Page title is "%s", but expected not to be.', $title)
                : \sprintf('Page title is not "%s".', $title),
            function () use ($title): string {
                $actual = $this->subject->title();

                return $this->negated
                    ? \sprintf('Expected title not %s. Actual: %s', \json_encode($title), \json_encode($actual))
                    : \sprintf('Expected title %s, but was %s', \json_encode($title), \json_encode($actual));
            }
        );
    }

    public function toHaveURL(string $url): void
    {
        if (!$this->subject instanceof PageInterface) {
            throw new \InvalidArgumentException('toHaveURL() can only be used with PageInterface');
        }

        $this->retryAssertion(
            fn () => $this->subject->url() === $url,
            !$this->negated,
            $this->negated
                ? \sprintf('Page URL is "%s", but expected not to be.', $url)
                : \sprintf('Page URL is not "%s".', $url),
            function () use ($url): string {
                $actual = $this->subject->url();

                return $this->negated
                    ? \sprintf('Expected URL not %s. Actual: %s', \json_encode($url), \json_encode($actual))
                    : \sprintf('Expected URL %s, but was %s', \json_encode($url), \json_encode($actual));
            }
        );
    }

    /**
     * Core retry assertion mechanism with configurable timeout and polling.
     */
    private function retryAssertion(callable $condition, bool $expectedResult, string $message, ?callable $failureMessageProvider = null): void
    {
        $startTime = \microtime(true);
        $endTime = $startTime + ($this->timeoutMs / 1000);

        $lastException = null;

        while (\microtime(true) < $endTime) {
            try {
                $actualResult = $condition();

                if ($actualResult === $expectedResult) {
                    // Assertion passed
                    // Increment PHPUnit's assertion count so consumers don't need addToAssertionCount
                    Assert::assertTrue(true);

                    return;
                }

                // Store the current state for final error message
                $lastException = new AssertionFailedError($message);
            } catch (\Throwable $e) {
                // Store exception but continue retrying
                $lastException = $e;
            }

            // Sleep before next attempt
            \usleep($this->pollIntervalMs * 1000);
        }

        // Timeout reached, throw the last exception or create timeout exception
        $finalMessage = $message;
        if (null !== $failureMessageProvider) {
            try {
                $computed = (string) $failureMessageProvider();
                if ('' !== $computed) {
                    $finalMessage = $computed;
                }
            } catch (\Throwable) {
                // ignore message computation errors
            }
        }

        if ($lastException instanceof AssertionFailedError) {
            throw $lastException;
        }

        if ($lastException) {
            // For other exceptions, wrap them in AssertionFailedError so PHPUnit treats them properly
            throw new AssertionFailedError(\sprintf('Assertion timed out after %dms: %s. Last error: %s', $this->timeoutMs, $finalMessage, $lastException->getMessage()), 0, $lastException);
        }

        // If no specific condition was met within timeout, throw assertion failure
        throw new AssertionFailedError(\sprintf('Assertion timed out after %dms: %s', $this->timeoutMs, $finalMessage));
    }

    public function toHaveCSS(string $name, string $value): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toHaveCSS() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => $this->subject->evaluate(\sprintf('(element) => window.getComputedStyle(element).%s', $name)) === $value,
            !$this->negated,
            $this->negated
                ? \sprintf('Locator CSS property "%s" is "%s", but expected not to be.', $name, $value)
                : \sprintf('Locator CSS property "%s" is not "%s".', $name, $value),
            function () use ($name, $value): string {
                $actual = (string) $this->subject->evaluate(\sprintf('(element) => window.getComputedStyle(element).%s', $name));

                return $this->negated
                    ? \sprintf('Expected CSS %s not %s. Actual: %s', \json_encode($name), \json_encode($value), \json_encode($actual))
                    : \sprintf('Expected CSS %s = %s, but was %s', \json_encode($name), \json_encode($value), \json_encode($actual));
            }
        );
    }
}
