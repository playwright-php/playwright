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

class SelectorChain
{
    /** @var array<string> */
    private array $selectors = [];

    public function __construct(string $selector)
    {
        $this->selectors[] = $selector;
    }

    public function append(string $selector): self
    {
        $this->selectors[] = $selector;

        return $this;
    }

    public function __toString(): string
    {
        return implode(' >> ', $this->selectors);
    }
}
