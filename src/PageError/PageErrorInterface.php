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

/**
 * A snapshot of an uncaught JavaScript error recorded by a page.
 *
 * @see https://playwright.dev/docs/api/class-page#page-page-errors
 */
interface PageErrorInterface
{
    /**
     * The JavaScript error name, such as 'Error' or 'TypeError'.
     */
    public function name(): string;

    /**
     * The error message, without an added error name or stack trace.
     */
    public function message(): string;

    /**
     * The JavaScript stack trace, or null when unavailable.
     */
    public function stack(): ?string;
}
