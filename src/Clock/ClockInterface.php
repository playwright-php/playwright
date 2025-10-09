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

/**
 * @see https://playwright.dev/docs/clock
 * @see https://playwright.dev/docs/api/class-clock
 */
interface ClockInterface
{
    /**
     * Advance the clock by jumping forward in time. Only fires due timers at most once.
     *
     * @param int|string $ticks Number of milliseconds or string like '01:00:00'
     */
    public function fastForward(int|string $ticks): void;

    /**
     * Install fake implementations for time-related functions.
     *
     * @param array{time?: int|string|\DateTimeInterface} $options
     */
    public function install(array $options = []): void;

    /**
     * Advance the clock by jumping forward in time and pause the time.
     * Only fires due timers at most once.
     */
    public function pauseAt(int|string|\DateTimeInterface $time): void;

    /**
     * Resumes timers. Once called, time resumes flowing, timers are fired as usual.
     */
    public function resume(): void;

    /**
     * Advance the clock, firing all the time-related callbacks.
     *
     * @param int|string $ticks Number of milliseconds or string like '01:00:00'
     */
    public function runFor(int|string $ticks): void;

    /**
     * Makes Date.now and new Date() return fixed fake time at all times, keeps all timers running.
     */
    public function setFixedTime(int|string|\DateTimeInterface $time): void;

    /**
     * Sets system time, but does not trigger any timers.
     */
    public function setSystemTime(int|string|\DateTimeInterface $time): void;
}
