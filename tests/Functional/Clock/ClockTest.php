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

namespace Playwright\Tests\Functional\Clock;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Clock\Clock;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Clock::class)]
class ClockTest extends FunctionalTestCase
{
    public function testInstallSetsInitialTime(): void
    {
        $startTime = 1704067200000;
        $this->context->clock()->install(['time' => $startTime]);

        $this->goto('/clock.html');

        $this->assertTimeNear('#time', $startTime, 1000);
    }

    public function testSetFixedTimeMakesDateNowFixed(): void
    {
        $startTime = 1704067200000;
        $this->context->clock()->install(['time' => 0]);
        $this->context->clock()->setFixedTime($startTime);

        $this->goto('/clock.html');

        $this->expect($this->page->locator('#time'))->toHaveText((string) $startTime);
    }

    public function testSetSystemTimeChangesDateNow(): void
    {
        $systemTime = 1704067200000;
        $this->context->clock()->install(['time' => 0]);

        $this->goto('/clock.html');

        $this->context->clock()->setSystemTime($systemTime);
        $this->page->click('#btn');

        $this->assertTimeNear('#time', $systemTime, 1000);
    }

    public function testPauseAtPausesClock(): void
    {
        $startTime = 1_600_000_000_000;
        $this->context->clock()->install(['time' => $startTime]);

        $this->goto('/clock.html');

        $targetTime = $startTime + 5_000;
        $this->context->clock()->pauseAt($targetTime);
        $this->page->click('#btn');

        $this->expect($this->page->locator('#time'))->toHaveText((string) $targetTime);
    }

    public function testFastForwardAdvancesTimers(): void
    {
        $startTime = 1_600_000_000_000;
        $this->context->clock()->install(['time' => $startTime]);

        $this->goto('/clock.html');
        $this->page->click('#schedule-long-timeout');
        $this->expect($this->page->locator('#long-timeout-status'))->toContainText('waiting');

        $this->context->clock()->fastForward(5000);

        $this->assertTimeNear('#long-timeout-status', $startTime + 5000, 200);
    }

    public function testRunForAdvancesTimers(): void
    {
        $startTime = 1_600_000_000_000;
        $this->context->clock()->install(['time' => $startTime]);

        $this->goto('/clock.html');
        $this->page->click('#schedule-short-timeout');
        $this->expect($this->page->locator('#short-timeout-status'))->toContainText('waiting');

        $this->context->clock()->runFor(2000);

        $this->assertTimeNear('#short-timeout-status', $startTime + 2000, 200);
    }

    public function testResumeResumesFromPause(): void
    {
        $startTime = 1_600_000_000_000;
        $this->context->clock()->install(['time' => $startTime]);

        $this->goto('/clock.html');
        $status = $this->page->locator('#short-timeout-status');
        $this->page->click('#schedule-short-timeout');
        $this->expect($status)->toHaveText('waiting');

        $this->context->clock()->pauseAt($startTime + 1000);
        $this->context->clock()->runFor(500);
        $this->expect($status)->toHaveText('waiting');

        $this->context->clock()->resume();
        $this->context->clock()->runFor(1000);

        $this->assertTimeNear('#short-timeout-status', $startTime + 2000, 300);
    }

    private function assertTimeNear(string $selector, int $expected, int $tolerance = 200): void
    {
        $text = $this->page->locator($selector)->textContent();
        self::assertIsString($text, sprintf('Element %s did not contain time text', $selector));

        $actual = (int) trim($text);
        $diff = abs($actual - $expected);

        self::assertLessThanOrEqual(
            $tolerance,
            $diff,
            sprintf('Expected %s time near %d (±%d), got %d (diff %d)', $selector, $expected, $tolerance, $actual, $diff)
        );
    }
}
