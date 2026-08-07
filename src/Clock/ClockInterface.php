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
 * Controls time in a browser context.
 *
 * Use it to install fake timers or to set and advance time deterministically.
 * Browser contexts expose the clock associated with their own page state.
 *
 * @see https://playwright.dev/docs/clock
 * @see https://playwright.dev/docs/api/class-clock
 */
interface ClockInterface
{
    /**
     * Jumps the clock forward.
     *
     * Runs timers that become due, but fires each callback at most once.
     * Accepts milliseconds or a duration string such as '01:00:00'.
     *
     * @param int|string $ticks Number of milliseconds or string like '01:00:00'
     */
    public function fastForward(int|string $ticks): void;

    /**
     * Installs the fake clock.
     *
     * Replaces time-related functions in this browser context with controlled implementations.
     * The optional time becomes the initial value used by the fake clock.
     *
     * @param array{time?: int|string|\DateTimeInterface} $options
     */
    public function install(array $options = []): void;

    /**
     * Advances time and pauses it.
     *
     * Runs timers that become due, but fires each callback at most once.
     * The time can be a timestamp, a duration string, or a DateTimeInterface.
     */
    public function pauseAt(int|string|\DateTimeInterface $time): void;

    /**
     * Resumes clock progression.
     *
     * Time starts flowing from its current value after a previous pause.
     * Timers then run according to their normal schedule.
     */
    public function resume(): void;

    /**
     * Runs the clock for a duration.
     *
     * Executes time-related callbacks that become due during the elapsed period.
     * Accepts milliseconds or a duration string such as '01:00:00'.
     *
     * @param int|string $ticks Number of milliseconds or string like '01:00:00'
     */
    public function runFor(int|string $ticks): void;

    /**
     * Fixes the reported date.
     *
     * Makes Date.now() and new Date() return the supplied fake time.
     * Timers continue running while date reads remain fixed.
     */
    public function setFixedTime(int|string|\DateTimeInterface $time): void;

    /**
     * Sets the system time.
     *
     * Changes the fake clock's current time without running due timers.
     * Use it when time must move without executing scheduled callbacks.
     */
    public function setSystemTime(int|string|\DateTimeInterface $time): void;
}
