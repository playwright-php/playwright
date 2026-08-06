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

namespace Playwright\Assertions\Internal;

use Playwright\Exception\TimeoutException;

final class Waiter
{
    public const DEFAULT_TIMEOUT_MS = 5000;

    /**
     * @param callable():bool $predicate return true when condition satisfied
     */
    public static function eventually(callable $predicate, int $timeoutMs = self::DEFAULT_TIMEOUT_MS, int $intervalMs = 50): void
    {
        $deadline = \hrtime(true) + ($timeoutMs * 1_000_000);
        do {
            if ($predicate()) {
                return;
            }
            \usleep($intervalMs * 1000);
        } while (\hrtime(true) < $deadline);

        throw new TimeoutException('Condition not met within timeout.');
    }
}
