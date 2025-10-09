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

namespace Playwright\Tests\Unit\Worker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\JSHandle\JSHandleInterface;
use Playwright\Transport\TransportInterface;
use Playwright\Worker\Worker;

#[CoversClass(Worker::class)]
final class WorkerTest extends TestCase
{
    public function testUrl(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $worker = new Worker($transport, 'worker_1', 'https://example/worker.js');
        $this->assertSame('https://example/worker.js', $worker->url());
    }

    public function testEvaluateSendsCommandAndReturnsResult(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'worker.evaluate',
                'workerId' => 'worker_1',
                'expression' => '1+2',
                'arg' => null,
            ])
            ->willReturn(['result' => 3]);

        $worker = new Worker($transport, 'worker_1', 'https://example/worker.js');
        $this->assertSame(3, $worker->evaluate('1+2'));
    }

    public function testEvaluateHandleReturnsJSHandle(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'worker.evaluateHandle',
                'workerId' => 'worker_1',
                'expression' => '() => document',
                'arg' => null,
            ])
            ->willReturn(['handleId' => 'handle_123']);

        $worker = new Worker($transport, 'worker_1', 'https://example/worker.js');
        $handle = $worker->evaluateHandle('() => document');
        $this->assertInstanceOf(JSHandleInterface::class, $handle);
    }

    public function testWaitForEventReturnsEventDataArray(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'worker.waitForEvent',
                'workerId' => 'worker_1',
                'event' => 'close',
                'options' => ['timeout' => 1000],
            ])
            ->willReturn(['eventData' => ['reason' => 'test']]);

        $worker = new Worker($transport, 'worker_1', 'https://example/worker.js');
        $data = $worker->waitForEvent('close', null, ['timeout' => 1000]);
        $this->assertSame('test', $data['reason'] ?? null);
    }
}
