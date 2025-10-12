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

namespace Playwright\Tests\Mocks;

use Psr\Clock\ClockInterface;

final class FixedClock implements ClockInterface
{
    private \DateTimeImmutable $now;

    public function __construct(?\DateTimeImmutable $now = null)
    {
        $this->now = $now ?? new \DateTimeImmutable('2024-01-01T00:00:00Z');
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function setNow(\DateTimeImmutable $now): void
    {
        $this->now = $now;
    }

    public function advanceMilliseconds(float $milliseconds): void
    {
        $microseconds = (int) round($milliseconds * 1000);
        $this->now = $this->now->modify(sprintf('+%d microseconds', $microseconds));
    }
}
