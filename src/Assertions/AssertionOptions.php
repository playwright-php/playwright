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

readonly class AssertionOptions
{
    public function __construct(
        public ?int $timeoutMs = null,
        public ?int $intervalMs = 100,
        public ?string $message = null,
        public ?bool $strict = null,
        public ?bool $ignoreCase = null,
        public ?bool $useInnerText = null,
    ) {
    }
}
