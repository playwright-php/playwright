<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Configuration\BrowserType;
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Tests\Mocks\MockPlaywrightServer;
use PlaywrightPHP\Tests\Mocks\MockProcessTransport;
use PlaywrightPHP\Tests\Mocks\TestLogger;

#[CoversNothing]
class MockedPlaywrightIntegrationTest extends TestCase
{
    private MockPlaywrightServer $mockServer;
    private MockProcessTransport $mockTransport;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->mockServer = MockPlaywrightServer::forBrowserTesting();
        $this->mockTransport = new MockProcessTransport($this->mockServer);
        $this->logger = new TestLogger();
    }

    #[Test]
    public function itCanHandleMockServerCommunication(): void
    {
        // Configure mock server responses
        $this->mockServer->setResponse('Playwright.version', ['version' => '1.40.0']);
        $this->mockServer->setResponse('Custom.method', ['data' => 'test']);

        $this->mockTransport->connect();

        // Test direct mock server communication
        $versionRequest = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'Playwright.version'];
        $versionResponse = $this->mockServer->handleRequest($versionRequest);

        $this->assertEquals('2.0', $versionResponse['jsonrpc']);
        $this->assertEquals(['version' => '1.40.0'], $versionResponse['result']);

        // Test transport communication
        $customResponse = $this->mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'Custom.method',
        ]);

        $this->assertEquals('test', $customResponse['result']['data']);

        // Verify requests were logged
        $this->assertCount(2, $this->mockServer->getRequests());
        $this->assertCount(1, $this->mockTransport->getSentMessages());
    }

    #[Test]
    public function itHandlesBrowserLaunchingSequence(): void
    {
        $this->mockTransport->connect();

        // Simulate browser launch sequence using pre-configured responses
        $browserResponse = $this->mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'Browser.launch',
            'params' => ['type' => 'chromium'],
        ]);

        $contextResponse = $this->mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'Browser.newContext',
        ]);

        $pageResponse = $this->mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'BrowserContext.newPage',
        ]);

        $this->assertEquals('browser_1', $browserResponse['result']['browserId']);
        $this->assertEquals('context_1', $contextResponse['result']['contextId']);
        $this->assertEquals('page_1', $pageResponse['result']['pageId']);

        $this->assertCount(3, $this->mockServer->getRequests());
        $this->assertCount(3, $this->mockTransport->getSentMessages());
    }

    #[Test]
    public function itHandlesPageNavigationCommands(): void
    {
        $this->mockTransport->connect();

        // Test page navigation sequence
        $gotoResponse = $this->mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'Page.goto',
            'params' => ['url' => 'https://example.com'],
        ]);

        $titleResponse = $this->mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'Page.title',
        ]);

        $screenshotResponse = $this->mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'Page.screenshot',
        ]);

        $this->assertEquals('https://example.com', $gotoResponse['result']['url']);
        $this->assertEquals('Mock Page Title', $titleResponse['result']['title']);
        $this->assertNotEmpty($screenshotResponse['result']['screenshot']);

        // Verify all commands were recorded
        $requests = $this->mockServer->getRequests();
        $this->assertCount(3, $requests);
        $this->assertEquals('Page.goto', $requests[0]['method']);
        $this->assertEquals('Page.title', $requests[1]['method']);
        $this->assertEquals('Page.screenshot', $requests[2]['method']);
    }

    #[Test]
    public function itHandlesErrorResponses(): void
    {
        // Configure error response
        $this->mockServer->setErrorResponse('Page.goto', 'Navigation timeout', 408);

        $this->mockTransport->connect();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mock error: Navigation timeout');

        $this->mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'Page.goto',
            'params' => ['url' => 'https://timeout.com'],
        ]);
    }

    #[Test]
    public function itCanClearMockServerState(): void
    {
        $this->mockTransport->connect();

        // Make some requests
        $this->mockTransport->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'Page.goto']);
        $this->mockTransport->send(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'Page.title']);

        $this->assertCount(2, $this->mockServer->getRequests());
        $this->assertCount(2, $this->mockTransport->getSentMessages());

        // Clear state
        $this->mockServer->clearRequests();
        $this->mockTransport->clearSentMessages();

        $this->assertCount(0, $this->mockServer->getRequests());
        $this->assertCount(0, $this->mockTransport->getSentMessages());
    }

    #[Test]
    public function itHandlesTransportConnection(): void
    {
        // Initially disconnected
        $this->assertFalse($this->mockTransport->isConnected());

        // Connect
        $this->mockTransport->connect();
        $this->assertTrue($this->mockTransport->isConnected());
        $this->assertTrue($this->mockServer->isRunning());

        // Disconnect
        $this->mockTransport->disconnect();
        $this->assertFalse($this->mockTransport->isConnected());
        $this->assertFalse($this->mockServer->isRunning());

        // Test that sending after disconnect fails
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transport not connected');

        $this->mockTransport->send(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'test']);
    }

    #[Test]
    public function itHandlesConfigurationIntegration(): void
    {
        // Test that configuration objects work with the test infrastructure
        $config = new PlaywrightConfig(
            nodePath: '/mock/node',
            browser: BrowserType::FIREFOX,
            headless: false,
            timeoutMs: 30000
        );

        $this->assertEquals(BrowserType::FIREFOX, $config->browser);
        $this->assertFalse($config->headless);
        $this->assertEquals('/mock/node', $config->nodePath);
        $this->assertEquals(30000, $config->timeoutMs);

        // Test that our mock server can handle browser-specific requests
        $this->mockServer->setResponse('Browser.firefox', [
            'browserType' => 'firefox',
            'version' => '120.0',
        ]);

        $this->mockTransport->connect();
        $response = $this->mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'Browser.firefox',
        ]);

        $this->assertEquals('firefox', $response['result']['browserType']);
    }

    protected function tearDown(): void
    {
        if ($this->mockTransport->isConnected()) {
            $this->mockTransport->disconnect();
        }
        $this->mockServer->stop();
    }
}
