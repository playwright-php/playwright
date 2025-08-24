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
use PlaywrightPHP\Configuration\PlaywrightConfigBuilder;
use PlaywrightPHP\Tests\Mocks\TestLogger;

#[CoversClass(PlaywrightConfigBuilder::class)]
class PlaywrightConfigBuilderTest extends TestCase
{
    private function clearEnvironment(): void
    {
        $envVars = [
            'PLAYWRIGHT_NODE_PATH',
            'PLAYWRIGHT_NODE_MIN_VERSION',
            'PW_BROWSER',
            'PW_CHANNEL',
            'PW_HEADLESS',
            'PW_TIMEOUT_MS',
            'PW_SLOWMO_MS',
            'PW_TRACING',
            'PW_TRACE_DIR',
            'PW_DOWNLOADS_DIR',
            'PW_VIDEOS_DIR',
            'PW_PROXY_SERVER',
            'PW_PROXY_USERNAME',
            'PW_PROXY_PASSWORD',
            'PW_PROXY_BYPASS',
        ];

        foreach ($envVars as $var) {
            putenv($var);
        }
    }

    protected function setUp(): void
    {
        $this->clearEnvironment();
    }

    protected function tearDown(): void
    {
        $this->clearEnvironment();
    }

    #[Test]
    public function itCanCreateBuilder(): void
    {
        $builder = PlaywrightConfigBuilder::create();

        $this->assertInstanceOf(PlaywrightConfigBuilder::class, $builder);
    }

    #[Test]
    public function itBuildsConfigWithDefaults(): void
    {
        $config = PlaywrightConfigBuilder::create()->build();

        $this->assertEquals(BrowserType::CHROMIUM, $config->browser);
        $this->assertTrue($config->headless);
        $this->assertEquals(30000, $config->timeoutMs);
        $this->assertEquals('18.0.0', $config->minNodeVersion);
        $this->assertEquals(0, $config->slowMoMs);
        $this->assertEmpty($config->args);
        $this->assertEmpty($config->env);
        $this->assertNull($config->nodePath);
        $this->assertNull($config->channel);
        $this->assertNull($config->downloadsDir);
        $this->assertNull($config->videosDir);
        $this->assertFalse($config->tracingEnabled);
    }

    #[Test]
    public function itSupportsFluentInterface(): void
    {
        $logger = new TestLogger();

        $config = PlaywrightConfigBuilder::create()
            ->withBrowser(BrowserType::FIREFOX)
            ->withHeadless(false)
            ->withTimeoutMs(45000)
            ->withSlowMoMs(1000)
            ->withNodePath('/usr/local/bin/node')
            ->withMinNodeVersion('20.0.0')
            ->withChannel('stable')
            ->withDownloadsDir('/tmp/downloads')
            ->withVideosDir('/tmp/videos')
            ->withLogger($logger)
            ->build();

        $this->assertEquals(BrowserType::FIREFOX, $config->browser);
        $this->assertFalse($config->headless);
        $this->assertEquals(45000, $config->timeoutMs);
        $this->assertEquals(1000, $config->slowMoMs);
        $this->assertEquals('/usr/local/bin/node', $config->nodePath);
        $this->assertEquals('20.0.0', $config->minNodeVersion);
        $this->assertEquals('stable', $config->channel);
        $this->assertEquals('/tmp/downloads', $config->downloadsDir);
        $this->assertEquals('/tmp/videos', $config->videosDir);
        $this->assertSame($logger, $config->logger);
    }

    #[Test]
    public function itManagesArguments(): void
    {
        $config = PlaywrightConfigBuilder::create()
            ->withArgs(['--arg1', '--arg2'])
            ->addArg('--arg3')
            ->addArg('--arg1')
            ->build();

        $this->assertEquals(['--arg1', '--arg2', '--arg3'], $config->args);
    }

    #[Test]
    public function itManagesEnvironmentVariables(): void
    {
        $config = PlaywrightConfigBuilder::create()
            ->withEnv(['VAR1' => 'value1', 'VAR2' => 'value2'])
            ->addEnv('VAR3', 'value3')
            ->addEnv('VAR1', 'new_value1')
            ->build();

        $expected = ['VAR1' => 'new_value1', 'VAR2' => 'value2', 'VAR3' => 'value3'];
        $this->assertEquals($expected, $config->env);
    }

    #[Test]
    public function itConfiguresTracing(): void
    {
        $config = PlaywrightConfigBuilder::create()
            ->withTracing(true, '/tmp/traces', true, false)
            ->build();

        $this->assertTrue($config->tracingEnabled);
        $this->assertEquals('/tmp/traces', $config->traceDir);
        $this->assertTrue($config->traceScreenshots);
        $this->assertFalse($config->traceSnapshots);
    }

    #[Test]
    public function itDisablesTracing(): void
    {
        $config = PlaywrightConfigBuilder::create()
            ->withTracing(true, '/tmp/traces')
            ->withTracing(false)
            ->build();

        $this->assertFalse($config->tracingEnabled);
        $this->assertNull($config->traceDir);
    }

    #[Test]
    public function itConfiguresProxy(): void
    {
        $config = PlaywrightConfigBuilder::create()
            ->withProxy('http://proxy.example.com:8080', 'user', 'pass', 'localhost')
            ->build();

        $expected = [
            'server' => 'http://proxy.example.com:8080',
            'username' => 'user',
            'password' => 'pass',
            'bypass' => 'localhost',
        ];
        $this->assertEquals($expected, $config->proxy);
    }

    #[Test]
    public function itClearsProxy(): void
    {
        $config = PlaywrightConfigBuilder::create()
            ->withProxy('http://proxy.example.com:8080')
            ->withProxy(null)
            ->build();

        $this->assertNull($config->proxy);
    }

    #[Test]
    public function itFiltersEmptyProxyValues(): void
    {
        $config = PlaywrightConfigBuilder::create()
            ->withProxy('http://proxy.example.com:8080', '', null, 'localhost')
            ->build();

        $expected = [
            'server' => 'http://proxy.example.com:8080',
            'bypass' => 'localhost',
        ];
        $this->assertEquals($expected, $config->proxy);
    }

    #[Test]
    public function itValidatesTimeouts(): void
    {
        $config = PlaywrightConfigBuilder::create()
            ->withTimeoutMs(-100)
            ->withSlowMoMs(-50)
            ->build();

        $this->assertEquals(0, $config->timeoutMs);
        $this->assertEquals(0, $config->slowMoMs);
    }

    #[Test]
    public function itHandlesValidationInBuild(): void
    {
        $config = PlaywrightConfigBuilder::create()
            ->withTimeoutMs(-100)
            ->build();

        $this->assertEquals(0, $config->timeoutMs);
    }

    #[Test]
    public function itCreatesFromEnvironmentVariables(): void
    {
        putenv('PLAYWRIGHT_NODE_PATH=/env/node');
        putenv('PLAYWRIGHT_NODE_MIN_VERSION=19.0.0');
        putenv('PW_BROWSER=firefox');
        putenv('PW_CHANNEL=nightly');
        putenv('PW_HEADLESS=false');
        putenv('PW_TIMEOUT_MS=60000');
        putenv('PW_SLOWMO_MS=500');
        putenv('PW_TRACING=true');
        putenv('PW_TRACE_DIR=/env/traces');
        putenv('PW_DOWNLOADS_DIR=/env/downloads');
        putenv('PW_VIDEOS_DIR=/env/videos');
        putenv('PW_PROXY_SERVER=http://env.proxy:8080');
        putenv('PW_PROXY_USERNAME=envuser');
        putenv('PW_PROXY_PASSWORD=envpass');
        putenv('PW_PROXY_BYPASS=localhost,127.0.0.1');

        $config = PlaywrightConfigBuilder::fromEnv()->build();

        $this->assertEquals('/env/node', $config->nodePath);
        $this->assertEquals('19.0.0', $config->minNodeVersion);
        $this->assertEquals(BrowserType::FIREFOX, $config->browser);
        $this->assertEquals('nightly', $config->channel);
        $this->assertFalse($config->headless);
        $this->assertEquals(60000, $config->timeoutMs);
        $this->assertEquals(500, $config->slowMoMs);
        $this->assertTrue($config->tracingEnabled);
        $this->assertEquals('/env/traces', $config->traceDir);
        $this->assertEquals('/env/downloads', $config->downloadsDir);
        $this->assertEquals('/env/videos', $config->videosDir);

        $expectedProxy = [
            'server' => 'http://env.proxy:8080',
            'username' => 'envuser',
            'password' => 'envpass',
            'bypass' => 'localhost,127.0.0.1',
        ];
        $this->assertEquals($expectedProxy, $config->proxy);
    }

    #[Test]
    public function itHandlesBrowserNameMappings(): void
    {
        putenv('PW_BROWSER=chrome');
        $config1 = PlaywrightConfigBuilder::fromEnv()->build();
        $this->assertEquals(BrowserType::CHROMIUM, $config1->browser);

        putenv('PW_BROWSER=webkit');
        $config2 = PlaywrightConfigBuilder::fromEnv()->build();
        $this->assertEquals(BrowserType::WEBKIT, $config2->browser);
    }

    #[Test]
    public function itIgnoresInvalidEnvironmentValues(): void
    {
        putenv('PW_BROWSER=invalid_browser');
        putenv('PW_TIMEOUT_MS=not_a_number');
        putenv('PW_HEADLESS=maybe');

        $config = PlaywrightConfigBuilder::fromEnv()->build();

        $this->assertEquals(BrowserType::CHROMIUM, $config->browser);
        $this->assertEquals(30000, $config->timeoutMs);
        $this->assertFalse($config->headless);
    }

    #[Test]
    public function itHandlesBooleanEnvironmentValues(): void
    {
        $truthyValues = ['1', 'true', 'TRUE', 'yes', 'YES', 'y', 'Y', 'on', 'ON'];
        $falsyValues = ['0', 'false', 'FALSE', 'no', 'NO', 'n', 'N', 'off', 'OFF', 'maybe'];

        foreach ($truthyValues as $value) {
            putenv("PW_HEADLESS=$value");
            $config = PlaywrightConfigBuilder::fromEnv()->build();
            $this->assertTrue($config->headless, "Value '$value' should be truthy");
        }

        foreach ($falsyValues as $value) {
            putenv("PW_HEADLESS=$value");
            $config = PlaywrightConfigBuilder::fromEnv()->build();
            $this->assertFalse($config->headless, "Value '$value' should be falsy");
        }

        putenv('PW_HEADLESS=');
        $config = PlaywrightConfigBuilder::fromEnv()->build();
        $this->assertTrue($config->headless);
    }

    #[Test]
    public function itSanitizesEmptyStrings(): void
    {
        $config = PlaywrightConfigBuilder::create()
            ->withChannel('')
            ->withDownloadsDir('')
            ->withVideosDir('')
            ->withTracing(true, '')
            ->build();

        $this->assertNull($config->channel);
        $this->assertNull($config->downloadsDir);
        $this->assertNull($config->videosDir);
        $this->assertNull($config->traceDir);
    }

    #[Test]
    public function itCombinesEnvironmentWithFluentInterface(): void
    {
        putenv('PW_BROWSER=firefox');
        putenv('PW_HEADLESS=true');

        $config = PlaywrightConfigBuilder::fromEnv()
            ->withBrowser(BrowserType::WEBKIT)
            ->withTimeoutMs(45000)
            ->build();

        $this->assertEquals(BrowserType::WEBKIT, $config->browser);
        $this->assertTrue($config->headless);
        $this->assertEquals(45000, $config->timeoutMs);
    }
}
