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

namespace Playwright\Locator;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface SelectorChainInterface
{
    public function append(string $selector): self;

    public function __toString(): string;

    public function toString(): string;
}
