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
use Playwright\Page\PageInterface;

final class PageAssertions implements PageAssertionsInterface
{
    private bool $negated = false;

    public function __construct(private PageInterface $page)
    {
    }

    public function not(): self
    {
        $this->negated = !$this->negated;

        return $this;
    }

    public function toHaveTitle(string|\Stringable $expected, ?AssertionOptions $options = null): self
    {
        $expected = (string) $expected;
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
            Waiter::eventually(fn () => $this->page->title() === $expected, $timeout, $interval);
        } catch (\Throwable) {
            $ok = false;
        }

        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            throw new AssertionException('Expected page title to match.', actual: $this->page->title(), expected: $expected);
        }

        return $this;
    }

    public function toHaveURL(string|\Stringable $expected, ?AssertionOptions $options = null): self
    {
        $expected = (string) $expected;
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
            Waiter::eventually(fn () => $this->page->url() === $expected, $timeout, $interval);
        } catch (\Throwable) {
            $ok = false;
        }

        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            throw new AssertionException('Expected page URL to match.', actual: $this->page->url(), expected: $expected);
        }

        return $this;
    }
}
