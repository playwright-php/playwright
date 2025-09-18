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

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class Expect implements ExpectInterface
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

    public function toBeEmpty(): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toBeEmpty() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => $this->subject->isEmpty(),
            !$this->negated,
            $this->negated ? 'Locator is empty, but expected not to be.' : 'Locator is not empty.'
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
                \assert($this->subject instanceof LocatorInterface);
                $actual = (string) ($this->subject->textContent() ?? '');

                return $this->negated
                    ? \sprintf('Expected text not to contain %s. Actual: %s', \json_encode($text), \json_encode($actual))
                    : \sprintf('Expected text to contain %s, but was %s', \json_encode($text), \json_encode($actual));
            }
        );
    }

    public function toContainText(string $text): void
    {
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
                \assert($this->subject instanceof LocatorInterface);
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
                \assert($this->subject instanceof LocatorInterface);
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
                \assert($this->subject instanceof LocatorInterface);
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
                \assert($this->subject instanceof LocatorInterface);
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

    public function toHaveFocus(): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toHaveFocus() can only be used with LocatorInterface');
        }

        $this->retryAssertion(
            fn () => (bool) $this->subject->evaluate('(element) => document.activeElement === element'),
            !$this->negated,
            $this->negated ? 'Locator has focus, but expected not to have.' : 'Locator has not focus.'
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
                \assert($this->subject instanceof PageInterface);
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
                \assert($this->subject instanceof PageInterface);
                $actual = $this->subject->url();

                return $this->negated
                    ? \sprintf('Expected URL not %s. Actual: %s', \json_encode($url), \json_encode($actual))
                    : \sprintf('Expected URL %s, but was %s', \json_encode($url), \json_encode($actual));
            }
        );
    }

    public function toHaveClass(string|array $class): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new \InvalidArgumentException('toHaveClass() can only be used with LocatorInterface');
        }

        $expectedClasses = is_array($class) ? $class : [$class];

        $this->retryAssertion(
            function () use ($expectedClasses) {
                \assert($this->subject instanceof LocatorInterface);
                $elementClass = $this->subject->getAttribute('class');
                if (null === $elementClass) {
                    return false;
                }
                $classes = explode(' ', $elementClass);
                foreach ($expectedClasses as $expectedClass) {
                    if (!in_array($expectedClass, $classes, true)) {
                        return false;
                    }
                }

                return true;
            },
            !$this->negated,
            $this->negated
                ? 'Locator has the specified class(es), but expected not to.'
                : 'Locator does not have the specified class(es).',
            function () use ($expectedClasses): string {
                \assert($this->subject instanceof LocatorInterface);
                $actual = (string) $this->subject->getAttribute('class');
                $expected = implode(' ', $expectedClasses);

                return $this->negated
                    ? \sprintf('Expected class list not to contain %s. Actual: %s', \json_encode($expected), \json_encode($actual))
                    : \sprintf('Expected class list to contain %s. Actual: %s', \json_encode($expected), \json_encode($actual));
            }
        );
    }

    public function toHaveId(string $id): void
    {
        $this->toHaveAttribute('id', $id);
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
                    Assert::assertEquals($expectedResult, $actualResult);

                    return;
                }

                $lastException = new AssertionFailedError($message);
            } catch (\Throwable $e) {
                $lastException = $e;
            }

            \usleep($this->pollIntervalMs * 1000);
        }

        $finalMessage = $message;
        if (null !== $failureMessageProvider) {
            try {
                $computed = $failureMessageProvider();
                if (\is_string($computed) && '' !== $computed) {
                    $finalMessage = $computed;
                }
            } catch (\Throwable) {
            }
        }

        if ($lastException instanceof AssertionFailedError) {
            throw $lastException;
        }

        if ($lastException) {
            throw new AssertionFailedError(\sprintf('Assertion timed out after %dms: %s. Last error: %s', $this->timeoutMs, $finalMessage, $lastException->getMessage()), 0, $lastException);
        }

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
                \assert($this->subject instanceof LocatorInterface);
                $evaluated = $this->subject->evaluate(\sprintf('(element) => window.getComputedStyle(element).%s', $name));
                $actual = match (true) {
                    \is_string($evaluated) => $evaluated,
                    \is_scalar($evaluated) => (string) $evaluated,
                    \is_null($evaluated) => 'null',
                    default => 'non-scalar',
                };

                return $this->negated
                    ? \sprintf('Expected CSS %s not %s. Actual: %s', \json_encode($name), \json_encode($value), \json_encode($actual))
                    : \sprintf('Expected CSS %s = %s, but was %s', \json_encode($name), \json_encode($value), \json_encode($actual));
            }
        );
    }
}
