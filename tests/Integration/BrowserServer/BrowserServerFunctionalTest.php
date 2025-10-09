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

namespace Playwright\Tests\Integration\BrowserServer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\BrowserServer\BrowserServer;
use Playwright\Exception\ProcessLaunchException;
use Playwright\Exception\TransportException;
use Playwright\PlaywrightFactory;

#[CoversClass(BrowserServer::class)]
final class BrowserServerFunctionalTest extends TestCase
{
    public function testLaunchServerChromiumAndClose(): void
    {
        $client = null;
        try {
            $client = PlaywrightFactory::create();
        } catch (\Throwable $e) {
            $this->markTestSkipped('PlaywrightFactory could not create client (likely missing Node/Playwright): '.$e->getMessage());
        }

        try {
            $server = $client->launchServer('chromium', ['headless' => true]);
            $endpoint = $server->wsEndpoint();

            $this->assertIsString($endpoint);
            $this->assertNotSame('', $endpoint);
            $this->assertTrue(str_starts_with($endpoint, 'ws://') || str_starts_with($endpoint, 'wss://') || str_contains($endpoint, 'ws+')); // unix or ws

            // Try graceful close
            $server->close();
        } catch (ProcessLaunchException|TransportException $e) {
            $this->markTestSkipped('launchServer not available or environment missing browsers: '.$e->getMessage());
        } catch (\Throwable $e) {
            $this->fail('Unexpected failure launching browser server: '.$e->getMessage());
        }
    }
}
