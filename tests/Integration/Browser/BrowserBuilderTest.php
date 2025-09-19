<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Browser\Browser;
use PlaywrightPHP\Browser\BrowserBuilder;
use PlaywrightPHP\Transport\TransportInterface;
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
    public function itCanSetChannel(): void
    {
        $result = $this->builder->withChannel('msedge');

        $this->assertSame($this->builder, $result);
    }

    #[Test]
    public function itPassesChannelOptionToTransport(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $message) {
                return 'launch' === $message['action']
                       && 'chromium' === $message['browser']
                       && isset($message['options']['channel'])
                       && 'msedge' === $message['options']['channel'];
            }))
            ->willReturn(['browserId' => 'browser-123', 'defaultContextId' => 'context-123', 'version' => '1.0']);

        $browser = $this->builder
            ->withChannel('msedge')
            ->launch();

        $this->assertInstanceOf(Browser::class, $browser);
    }

    #[Test]
    public function itIgnoresEmptyChannel(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $message) {
                return 'launch' === $message['action']
                       && 'chromium' === $message['browser']
                       && !isset($message['options']['channel']);
            }))
            ->willReturn(['browserId' => 'browser-123', 'defaultContextId' => 'context-123', 'version' => '1.0']);

        $browser = $this->builder
            ->withChannel('')
            ->launch();

        $this->assertInstanceOf(Browser::class, $browser);
    }
}
