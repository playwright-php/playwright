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

namespace Playwright\Tests\Unit\API;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\API\APIRequestContext;
use Playwright\Tracing\TracingInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(APIRequestContext::class)]
final class APIRequestContextTest extends TestCase
{
    private TransportInterface $transport;
    private APIRequestContext $context;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->context = new APIRequestContext($this->transport, 'context_1');
    }

    public function testTracingReturnsATracingInstance(): void
    {
        $this->assertInstanceOf(TracingInterface::class, $this->context->tracing());
    }

    public function testTracingTargetsTheSameContext(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'tracingStartHar',
                'contextId' => 'context_1',
                'path' => '/tmp/api.har',
                'options' => [],
            ])
            ->willReturn([]);

        $this->context->tracing()->startHar('/tmp/api.har');
    }
}
