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
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\MissingDependencyException;
use Playwright\Exception\PlaywrightException;
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
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
        $this->builder = new BrowserBuilder('chromium', $this->transport, $this->logger, new PlaywrightConfig());
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
        $firefoxBuilder = new BrowserBuilder('firefox', $this->transport, $this->logger, new PlaywrightConfig());
        $webkitBuilder = new BrowserBuilder('webkit', $this->transport, $this->logger, new PlaywrightConfig());

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

    #[Test]
    public function itSendsTheChannelInTheLaunchPayload(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $message): bool {
                return 'launch' === $message['action']
                    && 'msedge' === $message['options']['channel'];
            }))
            ->willReturn(['browserId' => 'b', 'defaultContextId' => 'c', 'version' => '1.0']);

        $this->builder->withChannel('msedge')->launch();
    }

    #[Test]
    public function itSendsTheFullProxyArrayInTheLaunchPayload(): void
    {
        $proxy = [
            'server' => 'http://proxy.local:8080',
            'username' => 'user',
            'password' => 'secret',
            'bypass' => 'localhost',
        ];

        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $message) use ($proxy): bool {
                return 'launch' === $message['action']
                    && $proxy === $message['options']['proxy'];
            }))
            ->willReturn(['browserId' => 'b', 'defaultContextId' => 'c', 'version' => '1.0']);

        $this->builder->withProxy($proxy)->launch();
    }

    #[Test]
    public function itSendsTheDownloadsPathInTheLaunchPayload(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $message): bool {
                return 'launch' === $message['action']
                    && '/tmp/downloads' === $message['options']['downloadsPath'];
            }))
            ->willReturn(['browserId' => 'b', 'defaultContextId' => 'c', 'version' => '1.0']);

        $this->builder->withDownloadsPath('/tmp/downloads')->launch();
    }

    #[Test]
    public function itDoesNotLogTheProxyPassword(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Launching browser', $this->callback(static function (array $context): bool {
                return '[REDACTED]' === $context['options']['proxy']['password'];
            }));

        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')
            ->willReturn(['browserId' => 'b', 'defaultContextId' => 'c', 'version' => '1.0']);

        $builder = new BrowserBuilder('chromium', $transport, $logger, new PlaywrightConfig());

        $builder->withProxy([
            'server' => 'http://proxy.local:8080',
            'username' => 'user',
            'password' => 'secret',
        ])->launch();
    }

    #[Test]
    public function itSendsContextOptionsSoTheDefaultContextRecordsVideo(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $message): bool {
                return 'launch' === $message['action']
                    && ['dir' => '/tmp/videos'] === $message['contextOptions']['recordVideo'];
            }))
            ->willReturn(['browserId' => 'b', 'defaultContextId' => 'c', 'version' => '1.0']);

        $builder = new BrowserBuilder(
            'chromium',
            $transport,
            new NullLogger(),
            new PlaywrightConfig(videosDir: '/tmp/videos'),
        );

        $builder->launch();
    }

    #[Test]
    public function itReportsMissingBrowsersWithTheInstallCommand(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn([
            'error' => "browserType.launch: Executable doesn't exist at /tmp/chrome\nPlease run the following command",
        ]);

        $builder = new BrowserBuilder('chromium', $transport, new NullLogger(), new PlaywrightConfig());

        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessageMatches('/vendor\/bin\/playwright-install/');

        $builder->launch();
    }

    #[Test]
    public function itReportsMissingHostDependenciesWithTheWithDepsCommand(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn([
            'error' => "browserType.launch: Host system is missing dependencies to run browsers.\nMissing libraries: libnss3",
        ]);

        $builder = new BrowserBuilder('chromium', $transport, new NullLogger(), new PlaywrightConfig());

        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessageMatches('/vendor\/bin\/playwright-install --with-deps/');

        $builder->launch();
    }

    #[Test]
    public function itLeavesUnrelatedLaunchErrorsAlone(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn(['error' => 'browserType.launch: something else went wrong']);

        $builder = new BrowserBuilder('chromium', $transport, new NullLogger(), new PlaywrightConfig());

        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('browserType.launch: something else went wrong');

        $builder->launch();
    }
}
