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

interface GenericAssertionsInterface
{
    /** @return $this */
    public function toEqual(mixed $expected, ?AssertionOptions $options = null): self;

    /** @return $this */
    public function toBeTruthy(?AssertionOptions $options = null): self;

    /** @return $this */
    public function toBeFalsy(?AssertionOptions $options = null): self;

    /** @return $this */
    public function toBeGreaterThan(int|float $expected, ?AssertionOptions $options = null): self;

    /** @return $this */
    public function not(): self; // flips to negated mode for next assertion
}
