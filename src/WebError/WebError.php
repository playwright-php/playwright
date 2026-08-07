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

namespace Playwright\WebError;

use Playwright\Page\PageInterface;

/**
 * Represents an unhandled page error.
 *
 * The error retains the exception reported by the browser runtime.
 * The originating page is available when Playwright can associate one with the error.
 */
final class WebError implements WebErrorInterface
{
    public function __construct(
        private readonly \Throwable $error,
        private readonly ?PageInterface $page = null,
    ) {
    }

    public function error(): \Throwable
    {
        return $this->error;
    }

    public function page(): ?PageInterface
    {
        return $this->page;
    }
}
