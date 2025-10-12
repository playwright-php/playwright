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
use Playwright\WebSocket\WebSocketRoute;
use Playwright\WebSocket\WebSocketRouteInterface;

#[CoversClass(WebSocketRoute::class)]
final class WebSocketRouteTest extends TestCase
{
    public function testUrlReturnsGivenUrl(): void
    {
        $transport = new MockTransport();
        $transport->connect();

        $route = new WebSocketRoute($transport, 'route_1', 'wss://example/ws');
        $this->assertSame('wss://example/ws', $route->url());
    }

    public function testCloseSendsMessage(): void
    {
        $captured = [];
        $transport = new MockTransport();
        $transport->connect();
        $transport->onSendAsync(function (array $message) use (&$captured): void {
            $captured[] = $message;
        });

        $route = new WebSocketRoute($transport, 'route_1', 'wss://example/ws');
        $route->close(['code' => 1000, 'reason' => 'Normal']);

        $this->assertNotEmpty($captured);
        $this->assertSame('websocketRoute.close', $captured[0]['action'] ?? null);
        $this->assertSame('route_1', $captured[0]['routeId'] ?? null);
        $this->assertSame(1000, $captured[0]['options']['code'] ?? null);
        $this->assertSame('Normal', $captured[0]['options']['reason'] ?? null);
    }

    public function testSendSendsMessage(): void
    {
        $transport = new MockTransport();
        $transport->connect();

        $route = new WebSocketRoute($transport, 'route_1', 'wss://example/ws');
        $route->send('ping');

        $messages = $transport->getAsyncMessages();
        $this->assertSame('websocketRoute.send', $messages[0]['action'] ?? null);
        $this->assertSame('route_1', $messages[0]['routeId'] ?? null);
        $this->assertSame('ping', $messages[0]['message'] ?? null);
    }

    public function testHandlersAreInvokedViaDispatchEvent(): void
    {
        $transport = new MockTransport();
        $transport->connect();

        $route = new WebSocketRoute($transport, 'route_1', 'wss://example/ws');

        $closed = null;
        $route->onClose(function ($e) use (&$closed) { $closed = $e; });
        $route->dispatchEvent('close', ['code' => 1000, 'reason' => 'bye']);
        $this->assertIsArray($closed);
        $this->assertSame(1000, $closed['code'] ?? null);
        $this->assertSame('bye', $closed['reason'] ?? null);

        $msg = null;
        $route->onMessage(function ($e) use (&$msg) { $msg = $e; });
        $route->dispatchEvent('message', ['payload' => 'hello', 'direction' => 'fromPage']);
        $this->assertIsArray($msg);
        $this->assertSame('hello', $msg['payload'] ?? null);
        $this->assertSame('fromPage', $msg['direction'] ?? null);
    }

    public function testConnectToServerReturnsNewInstanceWhenServerGivesId(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $transport->queueResponse(function (array $message): array {
            if (($message['action'] ?? null) === 'websocketRoute.connectToServer') {
                return ['serverRouteId' => 'route_server', 'url' => 'wss://server/ws'];
            }

            return [];
        });

        $route = new WebSocketRoute($transport, 'route_1', 'wss://example/ws');
        $serverRoute = $route->connectToServer();

        $this->assertInstanceOf(WebSocketRouteInterface::class, $serverRoute);
        $this->assertNotSame($route, $serverRoute);
        $this->assertSame('wss://server/ws', $serverRoute->url());
    }
}
