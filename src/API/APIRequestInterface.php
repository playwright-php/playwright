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

namespace Playwright\API;

/**
 * Creates API request contexts.
 *
 * Contexts send HTTP requests without creating a browser page.
 * Each context owns its cookies and request configuration.
 */
interface APIRequestInterface
{
    /**
     * Creates an API request context.
     *
     * Options define defaults such as baseURL, headers, and storage state.
     * Dispose the returned context after the required requests complete.
     *
     * @param array<string, mixed> $options
     */
    public function newContext(array $options = []): APIRequestContextInterface;
}
