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
 * The exception comes from browser runtime code that did not handle it.
 * A page is included when Playwright can associate the error with one.
 *
 * @see https://playwright.dev/docs/api/class-weberror
 */
interface WebErrorInterface
{
    /**
     * Returns the reported exception.
     *
     * The exception preserves the error type and message from the runtime.
     * Inspect it to classify or report the unhandled browser error.
     */
    public function error(): \Throwable;

    /**
     * Returns the originating page.
     *
     * A null result means the runtime did not associate the error with a page.
     * The returned page can identify its URL or owning browser context.
     */
    public function page(): ?PageInterface;
}
