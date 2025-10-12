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

namespace Playwright\Tests\Unit\WebSocket;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Transport\MockTransport;
use Playwright\WebSocket\WebSocket;

#[CoversClass(WebSocket::class)]
final class WebSocketTest extends TestCase
{
    public function testUrlReturnsGivenUrl(): void
    {
        $transport = new MockTransport();
        $transport->connect();

        $ws = new WebSocket($transport, 'ws_1', 'wss://example.test/socket');

        $this->assertSame('wss://example.test/socket', $ws->url());
    }

    public function testIsClosedBecomesTrueAfterCloseEvent(): void
    {
        $transport = new MockTransport();
        $transport->connect();

        $ws = new WebSocket($transport, 'ws_1', 'wss://example.test/socket');
        $this->assertFalse($ws->isClosed());

        // Simulate transport-dispatched close
        $ws->dispatchEvent('close', ['code' => 1000, 'reason' => 'Normal Closure']);

        $this->assertTrue($ws->isClosed());
    }

    public function testWaitForEventResolvesWithPredicate(): void
    {
        $transport = new MockTransport();
        $transport->connect();

        $ws = new WebSocket($transport, 'ws_1', 'wss://example.test/socket');

        $emitted = false;
        $transport->queueProcessEvent(function () use ($ws, &$emitted): void {
            if ($emitted) {
                return;
            }
            $ws->dispatchEvent('framereceived', ['payload' => 'hello']);
            $emitted = true;
        });

        $result = $ws->waitForEvent('framereceived', [
            'predicate' => fn ($e) => is_array($e) && (($e['payload'] ?? '') === 'hello'),
            'timeout' => 1000,
        ]);

        $this->assertIsArray($result);
        $this->assertSame('hello', $result['payload'] ?? null);
    }
}
