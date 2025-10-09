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

interface LocatorAssertionsInterface
{
    public function toBeVisible(?AssertionOptions $options = null): self;

    public function toBeHidden(?AssertionOptions $options = null): self;

    /**
     * @param string|array<string> $expected
     *
     * @return $this
     */
    public function toHaveText(string|array $expected, ?AssertionOptions $options = null): self;

    public function toHaveCount(int $expected, ?AssertionOptions $options = null): self;

    public function not(): self;
}
