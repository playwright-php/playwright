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

namespace Playwright\Testing\Assert;

use Playwright\Assertions\Failure\AssertionException;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;

final class Expectation
{
    private mixed $subject;
    private ?int $timeoutMs = null;
    private bool $negated = false;

    public function __construct(mixed $subject)
    {
        $this->subject = $subject;
    }

    public function withTimeout(int $milliseconds): self
    {
        $clone = clone $this;
        $clone->timeoutMs = $milliseconds;

        return $clone;
    }

    public function not(): self
    {
        $clone = clone $this;
        $clone->negated = !$this->negated;

        return $clone;
    }

    public function toBeVisible(?LocatorOptions $o = null): void
    {
        if (!$this->subject instanceof LocatorInterface) {
            throw new AssertionException('Subject is not a Locator');
        }
        $timeout = $this->timeoutMs;
        if (!is_int($timeout)) {
            $timeout = $o?->timeoutMs;
            if (!is_int($timeout)) {
                $timeout = 5000;
            }
        }
        $deadline = \microtime(true) + ($timeout / 1000);
        $last = null;

        do {
            $visible = $this->subject->isVisible();
            $pass = $this->negated ? !$visible : $visible;
            if ($pass) {
                return;
            }
            $last = $visible ? 'visible' : 'hidden';
            \usleep(50_000);
        } while (\microtime(true) < $deadline);

        $msg = $this->negated ? 'not to be visible' : 'to be visible';
        throw new AssertionException("Expected locator {$msg}, last state: {$last}");
    }

    public function toHaveURL(string|\Stringable|Regex $url, ?PageOptions $o = null): void
    {
        if (!$this->subject instanceof PageInterface) {
            throw new AssertionException('Subject is not a Page');
        }
        $timeout = $this->timeoutMs;
        if (!is_int($timeout)) {
            $timeout = $o?->timeoutMs;
            if (!is_int($timeout)) {
                $timeout = 5000;
            }
        }
        $deadline = \microtime(true) + ($timeout / 1000);

        $pattern = null;
        $isRegex = false;
        if ($url instanceof Regex) {
            $isRegex = true;
            $pattern = $url->pattern;
        } elseif ($url instanceof \Stringable) {
            $pattern = (string) $url;
            $isRegex = (\strlen($pattern) > 2 && '/' === $pattern[0] && \str_ends_with($pattern, '/'));
        } else {
            $pattern = $url;
            $isRegex = (\strlen($pattern) > 2 && '/' === $pattern[0] && \str_ends_with($pattern, '/'));
        }

        $last = null;
        do {
            $current = $this->subject->url();
            $match = $isRegex ? (bool) \preg_match($url instanceof Regex ? $url->pattern : $pattern, $current) : ($current === $pattern);
            $pass = $this->negated ? !$match : $match;
            if ($pass) {
                return;
            }
            $last = $current;
            \usleep(50_000);
        } while (\microtime(true) < $deadline);

        $expectText = $this->negated ? 'not to match' : 'to match';
        throw new AssertionException("Expected page URL {$expectText} {$pattern}, last observed: {$last}");
    }
}
