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

use Playwright\Transport\TransportInterface;

final class Clock implements ClockInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $contextId,
    ) {
    }

    public function fastForward(int|string $ticks): void
    {
        $this->transport->send([
            'action' => 'clock.fastForward',
            'contextId' => $this->contextId,
            'ticks' => $ticks,
        ]);
    }

    /**
     * @param array{time?: int|string|\DateTimeInterface} $options
     */
    public function install(array $options = []): void
    {
        $this->transport->send([
            'action' => 'clock.install',
            'contextId' => $this->contextId,
            'options' => $options,
        ]);
    }

    public function pauseAt(\DateTimeInterface|int|string $time): void
    {
        $timeValue = $time;
        if ($time instanceof \DateTimeInterface) {
            $timeValue = $time->getTimestamp() * 1000; // Convert to milliseconds
        }

        $this->transport->send([
            'action' => 'clock.pauseAt',
            'contextId' => $this->contextId,
            'time' => $timeValue,
        ]);
    }

    public function resume(): void
    {
        $this->transport->send([
            'action' => 'clock.resume',
            'contextId' => $this->contextId,
        ]);
    }

    public function runFor(int|string $ticks): void
    {
        $this->transport->send([
            'action' => 'clock.runFor',
            'contextId' => $this->contextId,
            'ticks' => $ticks,
        ]);
    }

    public function setFixedTime(\DateTimeInterface|int|string $time): void
    {
        $timeValue = $time;
        if ($time instanceof \DateTimeInterface) {
            $timeValue = $time->getTimestamp() * 1000; // Convert to milliseconds
        }

        $this->transport->send([
            'action' => 'clock.setFixedTime',
            'contextId' => $this->contextId,
            'time' => $timeValue,
        ]);
    }

    public function setSystemTime(\DateTimeInterface|int|string $time): void
    {
        $timeValue = $time;
        if ($time instanceof \DateTimeInterface) {
            $timeValue = $time->getTimestamp() * 1000; // Convert to milliseconds
        }

        $this->transport->send([
            'action' => 'clock.setSystemTime',
            'contextId' => $this->contextId,
            'time' => $timeValue,
        ]);
    }
}
