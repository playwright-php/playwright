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

namespace Playwright\Tests\Unit\BrowserServer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\BrowserServer\BrowserServer;
use Playwright\Transport\TransportInterface;

#[CoversClass(BrowserServer::class)]
final class BrowserServerTest extends TestCase
{
    public function testWsEndpointAndProcess(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $server = new BrowserServer($transport, 'server_1', 'ws://127.0.0.1:12345', 43210);

        $this->assertSame('ws://127.0.0.1:12345', $server->wsEndpoint());
        $this->assertSame(43210, $server->process());
    }

    public function testCloseSendsMessage(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'browserServer.close',
                'serverId' => 'server_1',
            ])
            ->willReturn([]);

        $server = new BrowserServer($transport, 'server_1', 'ws://endpoint');
        $server->close();
    }

    public function testKillSendsMessage(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'browserServer.kill',
                'serverId' => 'server_1',
            ])
            ->willReturn([]);

        $server = new BrowserServer($transport, 'server_1', 'ws://endpoint');
        $server->kill();
    }
}
