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

namespace Playwright\Tests\Unit\Client;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\BrowserServer\BrowserServerInterface;
use Playwright\Configuration\PlaywrightConfigBuilder;
use Playwright\PlaywrightClient;
use Playwright\Transport\TransportInterface;
use Psr\Log\NullLogger;

#[CoversClass(PlaywrightClient::class)]
final class PlaywrightClientLaunchServerTest extends TestCase
{
    public function testLaunchServerBuildsBrowserServer(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())->method('connect');
        $transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'launchServer',
                'browser' => 'chromium',
                'options' => [],
            ])
            ->willReturn([
                'serverId' => 'server_1',
                'wsEndpoint' => 'ws://127.0.0.1:12345',
                'pid' => 43210,
            ]);

        $client = new PlaywrightClient($transport, new NullLogger(), PlaywrightConfigBuilder::create()->build());
        $server = $client->launchServer('chromium');

        $this->assertInstanceOf(BrowserServerInterface::class, $server);
        $this->assertSame('ws://127.0.0.1:12345', $server->wsEndpoint());
        $this->assertSame(43210, $server->process());
    }
}
