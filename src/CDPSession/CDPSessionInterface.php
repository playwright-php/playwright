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

namespace Playwright\CDPSession;

/**
 * @see https://playwright.dev/docs/api/class-cdpsession
 */
interface CDPSessionInterface
{
    /**
     * Detaches the CDPSession from the target.
     */
    public function detach(): void;

    /**
     * Sends a message to the CDP session.
     *
     * @param string               $method The CDP method name
     * @param array<string, mixed> $params The method parameters
     *
     * @return array<string, mixed> The response from CDP
     */
    public function send(string $method, array $params = []): array;
}
