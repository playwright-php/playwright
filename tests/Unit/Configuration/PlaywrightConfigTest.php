<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Configuration\BrowserType;
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Tests\Mocks\TestLogger;

#[CoversClass(PlaywrightConfig::class)]
#[CoversClass(BrowserType::class)]
class PlaywrightConfigTest extends TestCase
{
    #[Test]
    public function itCanBeInstantiatedWithDefaults(): void
    {
        $config = new PlaywrightConfig();

        $this->assertInstanceOf(PlaywrightConfig::class, $config);
    }

    #[Test]
    public function itCanBeInstantiatedWithCustomNodePath(): void
    {
        $config = new PlaywrightConfig(nodePath: '/custom/node');

        $this->assertInstanceOf(PlaywrightConfig::class, $config);
    }

    #[Test]
    public function itCanBeInstantiatedWithCustomTimeout(): void
    {
        $config = new PlaywrightConfig(timeoutMs: 60);

        $this->assertInstanceOf(PlaywrightConfig::class, $config);
    }

    #[Test]
    public function itCanBeInstantiatedWithTracingEnabled(): void
    {
        $config = new PlaywrightConfig(tracingEnabled: true);

        $this->assertInstanceOf(PlaywrightConfig::class, $config);
    }

    #[Test]
    public function itCanBeInstantiatedWithHeadlessMode(): void
    {
        $config = new PlaywrightConfig(headless: false);

        $this->assertInstanceOf(PlaywrightConfig::class, $config);
    }

    #[Test]
    public function itCanBeInstantiatedWithAllParameters(): void
    {
        $config = new PlaywrightConfig(
            nodePath: '/usr/bin/node',
            timeoutMs: 45000,
            tracingEnabled: true,
            headless: false
        );

        $this->assertInstanceOf(PlaywrightConfig::class, $config);
    }

    #[Test]
    public function itCanBeInstantiatedWithZeroTimeout(): void
    {
        $config = new PlaywrightConfig(timeoutMs: 0);

        $this->assertInstanceOf(PlaywrightConfig::class, $config);
    }

    #[Test]
    public function itCanBeInstantiatedWithNegativeTimeout(): void
    {
        $config = new PlaywrightConfig(timeoutMs: -1);

        $this->assertInstanceOf(PlaywrightConfig::class, $config);
    }

    #[Test]
    public function itSupportsNewBrowserTypeConfiguration(): void
    {
        $config = new PlaywrightConfig(browser: BrowserType::FIREFOX);

        $this->assertEquals(BrowserType::FIREFOX, $config->browser);
        $this->assertEquals('firefox', $config->browser->value);
    }

    #[Test]
    public function itSupportsTracingConfiguration(): void
    {
        $config = new PlaywrightConfig(
            tracingEnabled: true,
            traceDir: '/tmp/traces',
            traceScreenshots: true,
            traceSnapshots: true
        );

        $this->assertTrue($config->tracingEnabled);
        $this->assertEquals('/tmp/traces', $config->traceDir);
        $this->assertTrue($config->traceScreenshots);
        $this->assertTrue($config->traceSnapshots);
    }

    #[Test]
    public function itSupportsProxyConfiguration(): void
    {
        $proxy = [
            'server' => 'http://proxy.example.com:8080',
            'username' => 'user',
            'password' => 'pass',
            'bypass' => 'localhost,127.0.0.1',
        ];
        $config = new PlaywrightConfig(proxy: $proxy);

        $this->assertEquals($proxy, $config->proxy);
    }

    #[Test]
    public function itSupportsDownloadsAndVideosDirectories(): void
    {
        $config = new PlaywrightConfig(
            downloadsDir: '/tmp/downloads',
            videosDir: '/tmp/videos'
        );

        $this->assertEquals('/tmp/downloads', $config->downloadsDir);
        $this->assertEquals('/tmp/videos', $config->videosDir);
    }

    #[Test]
    public function itSupportsLoggerConfiguration(): void
    {
        $logger = new TestLogger();
        $config = new PlaywrightConfig(logger: $logger);

        $this->assertSame($logger, $config->logger);
    }

    #[Test]
    public function itSupportsNodeConfiguration(): void
    {
        $config = new PlaywrightConfig(
            nodePath: '/usr/local/bin/node',
            minNodeVersion: '20.0.0'
        );

        $this->assertEquals('/usr/local/bin/node', $config->nodePath);
        $this->assertEquals('20.0.0', $config->minNodeVersion);
        $this->assertEquals('/usr/local/bin/node', $config->nodePath); // Legacy compatibility
    }

    #[Test]
    public function itSupportsTimeoutConfiguration(): void
    {
        $config = new PlaywrightConfig(
            timeoutMs: 45000,
            slowMoMs: 1000
        );

        $this->assertEquals(45000, $config->timeoutMs);
        $this->assertEquals(1000, $config->slowMoMs);
        $this->assertEquals(1000, $config->slowMoMs); // Verify slowMoMs again
    }

    #[Test]
    public function itProvidesDebuggingArray(): void
    {
        $logger = new TestLogger();
        $config = new PlaywrightConfig(
            browser: BrowserType::WEBKIT,
            headless: false,
            tracingEnabled: true,
            logger: $logger
        );

        $array = $config->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('webkit', $array['browser']);
        $this->assertFalse($array['headless']);
        $this->assertTrue($array['tracingEnabled']);
        $this->assertTrue($array['loggerProvided']);
    }

    #[Test]
    public function itHandlesLegacyParametersCorrectly(): void
    {
        // Test that legacy parameters still work
        $config = new PlaywrightConfig(
            nodePath: '/legacy/node',
            timeoutMs: 60000,
            tracingEnabled: true,
            headless: false
        );

        $this->assertEquals('/legacy/node', $config->nodePath);
        $this->assertEquals(60000, $config->timeoutMs);
        $this->assertTrue($config->tracingEnabled);
        $this->assertFalse($config->headless);
    }

    #[Test]
    public function itSupportsAllBrowserTypes(): void
    {
        $chromiumConfig = new PlaywrightConfig(browser: BrowserType::CHROMIUM);
        $firefoxConfig = new PlaywrightConfig(browser: BrowserType::FIREFOX);
        $webkitConfig = new PlaywrightConfig(browser: BrowserType::WEBKIT);

        $this->assertEquals(BrowserType::CHROMIUM, $chromiumConfig->browser);
        $this->assertEquals(BrowserType::FIREFOX, $firefoxConfig->browser);
        $this->assertEquals(BrowserType::WEBKIT, $webkitConfig->browser);
    }
}
