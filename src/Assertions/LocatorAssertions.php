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

namespace Playwright\Assertions;

use Playwright\Assertions\Failure\AssertionException;
use Playwright\Assertions\Internal\Waiter;
use Playwright\Locator\LocatorInterface;

final class LocatorAssertions implements LocatorAssertionsInterface
{
    private bool $negated = false;

    public function __construct(private LocatorInterface $locator)
    {
    }

    public function not(): self
    {
        $this->negated = !$this->negated;

        return $this;
    }

    public function toBeAttached(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => $this->locator->isAttached(),
            $options,
            'Expected locator to be attached.',
            'Expected locator to be detached.',
        );
    }

    public function toBeEditable(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => $this->locator->isEditable(),
            $options,
            'Expected locator to be editable.',
            'Expected locator not to be editable.',
        );
    }

    public function toBeVisible(?AssertionOptions $options = null): self
    {
        $timeout = $options?->timeoutMs;
        if (!is_int($timeout)) {
            $timeout = Waiter::DEFAULT_TIMEOUT_MS;
        }
        $interval = $options?->intervalMs;
        if (!is_int($interval)) {
            $interval = 50;
        }

        try {
            Waiter::eventually(fn () => $this->locator->isVisible(), $timeout, $interval);
        } catch (\Throwable $e) {
            $ok = false;
            if ($this->negated) {
                $ok = true;
                $this->negated = false;
            }
            if (!$ok) {
                $msg = $options?->message;
                if (null === $msg) {
                    $msg = 'Expected locator to be visible.';
                }
                throw new AssertionException($msg);
            }

            return $this;
        }

        $ok = true;
        if ($this->negated) {
            $ok = false;
            $this->negated = false;
        }
        if (!$ok) {
            $msg = $options?->message;
            if (null === $msg) {
                $msg = 'Expected locator to be hidden.';
            }
            throw new AssertionException($msg);
        }

        return $this;
    }

    public function toBeHidden(?AssertionOptions $options = null): self
    {
        return $this->not()->toBeVisible($options);
    }

    /**
     * @param string|array<string> $expected
     */
    public function toHaveText(string|array $expected, ?AssertionOptions $options = null): self
    {
        $timeout = $options?->timeoutMs;
        if (!is_int($timeout)) {
            $timeout = Waiter::DEFAULT_TIMEOUT_MS;
        }
        $useInner = $options?->useInnerText;
        if (!is_bool($useInner)) {
            $useInner = false;
        }
        $interval = $options?->intervalMs;
        if (!is_int($interval)) {
            $interval = 50;
        }

        $predicate = function () use ($expected, $useInner) {
            $actual = \is_array($expected)
                ? ($useInner ? $this->locator->allInnerTexts() : $this->locator->allTextContents())
                : ($useInner ? $this->locator->innerText() : $this->locator->textContent());

            return $actual === $expected;
        };

        $ok = true;
        try {
            Waiter::eventually($predicate, $timeout, $interval);
        } catch (\Throwable) {
            $ok = false;
        }

        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }

        if (!$ok) {
            $msg = $options?->message;
            if (null === $msg) {
                $msg = 'Expected locator to have text.';
            }
            throw new AssertionException($msg, actual: $useInner ? $this->locator->innerText() : $this->locator->textContent(), expected: $expected);
        }

        return $this;
    }

    public function toHaveCount(int $expected, ?AssertionOptions $options = null): self
    {
        $timeout = $options?->timeoutMs;
        if (!is_int($timeout)) {
            $timeout = Waiter::DEFAULT_TIMEOUT_MS;
        }
        $interval = $options?->intervalMs;
        if (!is_int($interval)) {
            $interval = 50;
        }

        $ok = true;
        try {
            Waiter::eventually(fn () => $this->locator->count() === $expected, $timeout, $interval);
        } catch (\Throwable) {
            $ok = false;
        }

        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            throw new AssertionException('Expected locator count to match.', actual: $this->locator->count(), expected: $expected);
        }

        return $this;
    }

    /**
     * @param callable(): bool $predicate
     */
    private function assertState(callable $predicate, ?AssertionOptions $options, string $expectedMessage, string $negatedMessage): self
    {
        $timeout = $options?->timeoutMs;
        if (!is_int($timeout)) {
            $timeout = Waiter::DEFAULT_TIMEOUT_MS;
        }
        $interval = $options?->intervalMs;
        if (!is_int($interval)) {
            $interval = 50;
        }

        $ok = true;
        try {
            Waiter::eventually($predicate, $timeout, $interval);
        } catch (\Throwable) {
            $ok = false;
        }

        $wasNegated = $this->negated;
        if ($wasNegated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            $message = $options instanceof AssertionOptions ? $options->message : null;
            throw new AssertionException($message ?? ($wasNegated ? $negatedMessage : $expectedMessage));
        }

        return $this;
    }
}
