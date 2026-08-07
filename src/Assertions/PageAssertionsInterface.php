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

interface PageAssertionsInterface
{
    /** @return $this */
    public function toHaveTitle(string|\Stringable $expected, ?AssertionOptions $options = null): self;

    /** @return $this */
    public function toHaveURL(string|\Stringable $expected, ?AssertionOptions $options = null): self;

    /**
     * Asserts the ARIA snapshot of the page body equals the expectation.
     *
     * Compares the YAML as text, ignoring blank lines, trailing whitespace and
     * the indentation shared by every line. The expectation describes the whole
     * body subtree, not a subset of it.
     *
     * @return $this
     */
    public function toMatchAriaSnapshot(string $expected, ?AssertionOptions $options = null): self;

    /** @return $this */
    public function not(): self;

    public function withTimeout(int $timeoutMs): self;

    public function withPollInterval(int $pollIntervalMs): self;
}
