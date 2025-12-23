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

namespace Playwright\Tests\Unit\Clock;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Clock\Clock;
use Playwright\Transport\MockTransport;

#[CoversClass(Clock::class)]
final class ClockTest extends TestCase
{
    public function testFastForwardSendsIntegerTicks(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $transport->queueResponse([]);

        $clock = new Clock($transport, 'ctx_ff_1');
        $clock->fastForward(5000);

        $sent = $transport->getSentMessages();
        $this->assertCount(1, $sent);
        $this->assertSame('clock.fastForward', $sent[0]['action']);
        $this->assertSame('ctx_ff_1', $sent[0]['contextId']);
        $this->assertSame(5000, $sent[0]['ticks']);
    }

    public function testFastForwardSendsStringTicks(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $transport->queueResponse([]);

        $clock = new Clock($transport, 'ctx_ff_2');
        $clock->fastForward('01:00:00');

        $sent = $transport->getSentMessages();
        $this->assertSame('clock.fastForward', $sent[0]['action']);
        $this->assertSame('01:00:00', $sent[0]['ticks']);
    }

    public function testInstallForwardsOptionsIncludingDateTime(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $transport->queueResponse([]);

        $clock = new Clock($transport, 'ctx_install_1');
        $dt = new \DateTimeImmutable('2025-01-01T00:00:00Z');

        $clock->install(['time' => $dt]);

        $sent = $transport->getSentMessages();
        $this->assertSame('clock.install', $sent[0]['action']);
        $this->assertArrayHasKey('options', $sent[0]);
        $this->assertSame($dt, $sent[0]['options']['time']);
    }

    public function testPauseAtConvertsDateTimeToMilliseconds(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $transport->queueResponse([]);

        $clock = new Clock($transport, 'ctx_pause_1');
        $dt = new \DateTimeImmutable('2025-02-02T12:34:56Z');

        $clock->pauseAt($dt);

        $sent = $transport->getSentMessages();
        $this->assertSame('clock.pauseAt', $sent[0]['action']);
        $this->assertSame($dt->getTimestamp() * 1000, $sent[0]['time']);
    }

    public function testResumeSendsMessage(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $transport->queueResponse([]);

        $clock = new Clock($transport, 'ctx_resume_1');
        $clock->resume();

        $sent = $transport->getSentMessages();
        $this->assertSame('clock.resume', $sent[0]['action']);
    }

    public function testRunForSendsTicks(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $transport->queueResponse([]);

        $clock = new Clock($transport, 'ctx_runfor_1');
        $clock->runFor(1234);

        $sent = $transport->getSentMessages();
        $this->assertSame('clock.runFor', $sent[0]['action']);
        $this->assertSame(1234, $sent[0]['ticks']);
    }

    public function testSetFixedTimeConvertsDateTimeToMilliseconds(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $transport->queueResponse([]);

        $clock = new Clock($transport, 'ctx_fixed_1');
        $dt = new \DateTimeImmutable('2025-03-03T00:00:00Z');

        $clock->setFixedTime($dt);

        $sent = $transport->getSentMessages();
        $this->assertSame('clock.setFixedTime', $sent[0]['action']);
        $this->assertSame($dt->getTimestamp() * 1000, $sent[0]['time']);
    }

    public function testSetSystemTimeConvertsDateTimeToMilliseconds(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $transport->queueResponse([]);

        $clock = new Clock($transport, 'ctx_system_1');
        $dt = new \DateTimeImmutable('2025-04-04T00:00:00Z');

        $clock->setSystemTime($dt);

        $sent = $transport->getSentMessages();
        $this->assertSame('clock.setSystemTime', $sent[0]['action']);
        $this->assertSame($dt->getTimestamp() * 1000, $sent[0]['time']);
    }
}
