<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Locator;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class SelectorChain
{
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
