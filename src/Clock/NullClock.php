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

namespace Playwright\Clock;

final class NullClock implements ClockInterface
{
    public function fastForward(int|string $ticks): void
    {
    }

    /**
     * @param array{time?: int|string|\DateTimeInterface} $options
     */
    public function install(array $options = []): void
    {
    }

    public function pauseAt(\DateTimeInterface|int|string $time): void
    {
    }

    public function resume(): void
    {
    }

    public function runFor(int|string $ticks): void
    {
    }

    public function setFixedTime(\DateTimeInterface|int|string $time): void
    {
    }

    public function setSystemTime(\DateTimeInterface|int|string $time): void
    {
    }
}
