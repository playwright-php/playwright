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
 * @see https://playwright.dev/docs/api/class-weberror
 */
interface WebErrorInterface
{
    /**
     * Unhandled error object.
     */
    public function error(): \Throwable;

    /**
     * The page that produced this unhandled exception, if any.
     */
    public function page(): ?PageInterface;
}
