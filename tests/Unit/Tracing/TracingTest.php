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

namespace Playwright\Tests\Unit\Tracing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Tracing\Tracing;
use Playwright\Transport\TransportInterface;

#[CoversClass(Tracing::class)]
final class TracingTest extends TestCase
{
    private TransportInterface $transport;
    private Tracing $tracing;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->tracing = new Tracing($this->transport, 'context_1');
    }

    public function testStartSendsOptions(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'tracingStart',
                'contextId' => 'context_1',
                'options' => ['screenshots' => true, 'snapshots' => true],
            ])
            ->willReturn([]);

        $this->tracing->start(['screenshots' => true, 'snapshots' => true]);
    }

    public function testStopSendsPath(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'tracingStop',
                'contextId' => 'context_1',
                'options' => ['path' => '/tmp/trace.zip'],
            ])
            ->willReturn([]);

        $this->tracing->stop(['path' => '/tmp/trace.zip']);
    }

    public function testGroupSendsNameWithoutLocation(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'tracingGroup',
                'contextId' => 'context_1',
                'name' => 'my step',
            ])
            ->willReturn([]);

        $this->tracing->group('my step');
    }

    public function testGroupSendsLocationWhenProvided(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'tracingGroup',
                'contextId' => 'context_1',
                'name' => 'my step',
                'location' => 'tests/MyTest.php',
            ])
            ->willReturn([]);

        $this->tracing->group('my step', 'tests/MyTest.php');
    }

    public function testStartHarSendsPathAndOptions(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'tracingStartHar',
                'contextId' => 'context_1',
                'path' => '/tmp/network.har',
                'options' => ['mode' => 'minimal', 'urlFilter' => '**/api/**'],
            ])
            ->willReturn([]);

        $this->tracing->startHar('/tmp/network.har', ['mode' => 'minimal', 'urlFilter' => '**/api/**']);
    }

    public function testStartHarSendsEmptyOptionsByDefault(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'tracingStartHar',
                'contextId' => 'context_1',
                'path' => '/tmp/network.har',
                'options' => [],
            ])
            ->willReturn([]);

        $this->tracing->startHar('/tmp/network.har');
    }

    public function testStopHarSendsAction(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'tracingStopHar',
                'contextId' => 'context_1',
            ])
            ->willReturn([]);

        $this->tracing->stopHar();
    }

    public function testGroupEndSendsAction(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'tracingGroupEnd',
                'contextId' => 'context_1',
            ])
            ->willReturn([]);

        $this->tracing->groupEnd();
    }
}
