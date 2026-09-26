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

use Playwright\Assertions\Options\ToHaveScreenshotOptions;

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

    /**
     * Asserts a stable screenshot of the page matches the baseline image at
     * $path, a .png or a lossless .webp.
     *
     * Screenshots are taken until two consecutive ones are identical, then
     * compared with the baseline by Playwright's own comparator. A missing
     * baseline is written and the assertion fails; PLAYWRIGHT_UPDATE_SNAPSHOTS
     * ("none", "missing", "changed", "all") controls what gets rewritten. On
     * failure, the expected, actual and diff images are written to the
     * screenshot output directory, test-failures/snapshots/ by default, and
     * removed once the baseline matches or is recorded again. Negation throws
     * a LogicException.
     */
    public function toHaveScreenshot(string $path, ?ToHaveScreenshotOptions $options = null): self;

    /** @return $this */
    public function not(): self;

    public function withTimeout(int $timeoutMs): self;

    public function withPollInterval(int $pollIntervalMs): self;
}
