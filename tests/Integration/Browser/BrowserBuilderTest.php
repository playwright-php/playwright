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

namespace Playwright\Tests\Integration\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\Browser;
use Playwright\Browser\BrowserBuilder;
use Playwright\Transport\TransportInterface;
use Psr\Log\NullLogger;

#[CoversClass(BrowserBuilder::class)]
class BrowserBuilderTest extends TestCase
{
    private BrowserBuilder $builder;
    private TransportInterface $transport;
    private NullLogger $logger;

    public function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->logger = new NullLogger();
        $this->builder = new BrowserBuilder('chromium', $this->transport, $this->logger);
    }

    #[Test]
    public function itCanBeInstantiated(): void
    {
        $this->assertInstanceOf(BrowserBuilder::class, $this->builder);
    }

    #[Test]
    public function itCanSetHeadlessMode(): void
    {
        $result = $this->builder->withHeadless(true);

        $this->assertSame($this->builder, $result);
    }

    #[Test]
    public function itCanSetHeadedMode(): void
    {
        $result = $this->builder->withHeadless(false);

        $this->assertSame($this->builder, $result);
    }

    #[Test]
    public function itCanSetSlowMo(): void
    {
        $result = $this->builder->withSlowMo(100);

        $this->assertSame($this->builder, $result);
    }

    #[Test]
    public function itCanSetArgs(): void
    {
        $result = $this->builder->withArgs(['--no-sandbox', '--disable-dev-shm-usage']);

        $this->assertSame($this->builder, $result);
    }

    #[Test]
    public function itCanSetInspector(): void
    {
        $result = $this->builder->withInspector();

        $this->assertSame($this->builder, $result);
    }

    #[Test]
    public function itCanChainMethods(): void
    {
        $result = $this->builder
            ->withHeadless(true)
            ->withSlowMo(50)
            ->withArgs(['--no-sandbox'])
            ->withInspector();

        $this->assertSame($this->builder, $result);
    }

    #[Test]
    public function itCanLaunchBrowser(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $message) {
                return 'launch' === $message['action']
                       && 'chromium' === $message['browser'];
            }))
            ->willReturn(['browserId' => 'browser-123', 'defaultContextId' => 'context-123', 'version' => '1.0']);

        $browser = $this->builder->launch();

        $this->assertInstanceOf(Browser::class, $browser);
    }

    #[Test]
    public function itCanLaunchBrowserWithOptions(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $message) {
                return 'launch' === $message['action']
                       && 'chromium' === $message['browser']
                       && isset($message['options']);
            }))
            ->willReturn(['browserId' => 'browser-123', 'defaultContextId' => 'context-123', 'version' => '1.0']);

        $browser = $this->builder
            ->withHeadless(true)
            ->withSlowMo(100)
            ->launch();

        $this->assertInstanceOf(Browser::class, $browser);
    }

    #[Test]
    public function itWorksWithDifferentBrowserTypes(): void
    {
        $firefoxBuilder = new BrowserBuilder('firefox', $this->transport, $this->logger);
        $webkitBuilder = new BrowserBuilder('webkit', $this->transport, $this->logger);

        $this->assertInstanceOf(BrowserBuilder::class, $firefoxBuilder);
        $this->assertInstanceOf(BrowserBuilder::class, $webkitBuilder);
    }

    #[Test]
    public function itCanConnectToExistingBrowserServer(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $message) {
                return 'connect' === $message['action']
                    && 'chromium' === $message['browser']
                    && 'ws://127.0.0.1:12345' === $message['wsEndpoint'];
            }))
            ->willReturn(['browserId' => 'browser-456', 'defaultContextId' => 'context-456', 'version' => '1.1']);

        $browser = $this->builder->connect('ws://127.0.0.1:12345');

        $this->assertInstanceOf(Browser::class, $browser);
    }

    #[Test]
    public function itCanConnectOverCDP(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $message) {
                return 'connectOverCDP' === $message['action']
                    && 'chromium' === $message['browser']
                    && 'http://localhost:9222' === $message['endpointURL'];
            }))
            ->willReturn(['browserId' => 'browser-789', 'defaultContextId' => 'context-789', 'version' => '1.2']);

        $browser = $this->builder->connectOverCDP('http://localhost:9222');

        $this->assertInstanceOf(Browser::class, $browser);
    }
}
