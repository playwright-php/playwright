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

namespace Playwright\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserBuilder;
use Playwright\Configuration\PlaywrightConfigBuilder;
use Playwright\PlaywrightClient;
use Playwright\Transport\TransportInterface;
use Psr\Log\NullLogger;

#[CoversClass(PlaywrightClient::class)]
class PlaywrightClientTest extends TestCase
{
    private PlaywrightClient $client;
    private TransportInterface $transport;
    private NullLogger $logger;

    public function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->logger = new NullLogger();
        $config = PlaywrightConfigBuilder::create()->build();
        $this->client = new PlaywrightClient($this->transport, $this->logger, $config);
    }

    #[Test]
    public function itCanBeInstantiated(): void
    {
        $this->assertInstanceOf(PlaywrightClient::class, $this->client);
    }

    #[Test]
    public function itCanGetChromiumBuilder(): void
    {
        $this->transport->expects($this->once())
            ->method('connect');

        $builder = $this->client->chromium();

        $this->assertInstanceOf(BrowserBuilder::class, $builder);
    }

    #[Test]
    public function itCanGetFirefoxBuilder(): void
    {
        $this->transport->expects($this->once())
            ->method('connect');

        $builder = $this->client->firefox();

        $this->assertInstanceOf(BrowserBuilder::class, $builder);
    }

    #[Test]
    public function itCanGetWebkitBuilder(): void
    {
        $this->transport->expects($this->once())
            ->method('connect');

        $builder = $this->client->webkit();

        $this->assertInstanceOf(BrowserBuilder::class, $builder);
    }

    #[Test]
    public function itConnectsTransportOnlyOnce(): void
    {
        $this->transport->expects($this->once())
            ->method('connect');

        $this->client->chromium();
        $this->client->firefox();
        $this->client->webkit();
    }

    #[Test]
    public function itCanCloseConnection(): void
    {
        $this->transport->expects($this->once())
            ->method('connect');
        $this->transport->expects($this->once())
            ->method('disconnect');

        $this->client->chromium();
        $this->client->close();
    }

    #[Test]
    public function itCanCloseConnectionSafely(): void
    {
        $this->transport->expects($this->never())
            ->method('disconnect');

        $this->client->close();
    }

    #[Test]
    public function itClosesConnectionOnDestruct(): void
    {
        $this->transport->expects($this->once())
            ->method('connect');
        $this->transport->expects($this->once())
            ->method('disconnect');

        $this->client->chromium();
        unset($this->client);
    }
}
