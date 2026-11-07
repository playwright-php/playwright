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

namespace Playwright\PageError;

final readonly class PageError implements PageErrorInterface
{
    public function __construct(
        private string $name,
        private string $message,
        private ?string $stack = null,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function stack(): ?string
    {
        return $this->stack;
    }
}
