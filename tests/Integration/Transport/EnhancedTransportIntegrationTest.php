<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Configuration\BrowserType;
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Node\NodeBinaryResolver;
use PlaywrightPHP\Tests\Mocks\MockPlaywrightServer;
use PlaywrightPHP\Tests\Mocks\MockProcessTransport;
use PlaywrightPHP\Tests\Mocks\TestLogger;
use PlaywrightPHP\Transport\JsonRpc\JsonRpcClient;
use PlaywrightPHP\Transport\JsonRpc\ProcessLauncher;

#[CoversClass(JsonRpcClient::class)]
#[CoversClass(ProcessLauncher::class)]
#[CoversClass(NodeBinaryResolver::class)]
class EnhancedTransportIntegrationTest extends TestCase
{
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();
    }

    #[Test]
    public function itIntegratesNodeDetectionWithConfiguration(): void
    {
        $resolver = new NodeBinaryResolver(logger: $this->logger);

        try {
            $nodePath = $resolver->resolve();
            $this->assertNotEmpty($nodePath);
            $this->assertFileExists($nodePath);

            // Create config with detected Node.js
            $config = new PlaywrightConfig(
                nodePath: $nodePath,
                browser: BrowserType::CHROMIUM,
                headless: true
            );

            // Test that ProcessLauncher can be created with the logger
            $launcher = new ProcessLauncher($this->logger);
            $this->assertInstanceOf(ProcessLauncher::class, $launcher);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Node.js binary not found')) {
                $this->markTestSkipped('Node.js not available in test environment');
            }
            throw $e;
        }
    }

    #[Test]
    public function itWorksWithMockTransportInfrastructure(): void
    {
        $mockServer = new MockPlaywrightServer();

        // Configure responses
        $mockServer->setResponse('Page.goto', function ($params) {
            // Simulate slow response to test timeout handling
            usleep(10000); // 10ms delay - reasonable for testing

            return ['loaderId' => 'slow_loader', 'url' => $params['url'] ?? ''];
        });

        $mockTransport = new MockProcessTransport($mockServer);
        $mockTransport->connect();

        // Test transport functionality
        $response = $mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'Page.goto',
            'params' => ['url' => 'https://example.com'],
        ]);

        $this->assertEquals('slow_loader', $response['result']['loaderId']);
        $this->assertEquals('https://example.com', $response['result']['url']);
    }

    #[Test]
    public function itHandlesComplexTransportSequences(): void
    {
        $mockServer = MockPlaywrightServer::forPageTesting();
        $mockTransport = new MockProcessTransport($mockServer);
        $mockTransport->connect();

        // Simulate complex page interaction sequence
        $responses = [];

        $responses['browser'] = $mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'Browser.launch',
            'params' => ['type' => 'chromium'],
        ]);

        $responses['context'] = $mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'Browser.newContext',
        ]);

        $responses['page'] = $mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'BrowserContext.newPage',
        ]);

        $responses['goto'] = $mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'Page.goto',
            'params' => ['url' => 'https://example.com'],
        ]);

        $responses['title'] = $mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 5,
            'method' => 'Page.title',
        ]);

        // Verify the sequence worked
        $this->assertEquals('browser_1', $responses['browser']['result']['browserId']);
        $this->assertEquals('context_1', $responses['context']['result']['contextId']);
        $this->assertEquals('page_1', $responses['page']['result']['pageId']);
        $this->assertEquals('https://example.com', $responses['goto']['result']['url']);
        $this->assertEquals('Mock Page Title', $responses['title']['result']['title']);

        // Verify all requests were logged
        $requests = $mockServer->getRequests();
        $this->assertCount(5, $requests);

        $expectedMethods = [
            'Browser.launch',
            'Browser.newContext',
            'BrowserContext.newPage',
            'Page.goto',
            'Page.title',
        ];

        foreach ($expectedMethods as $i => $expectedMethod) {
            $this->assertEquals($expectedMethod, $requests[$i]['method']);
        }
    }

    #[Test]
    public function itHandlesTransportConnectionStates(): void
    {
        $mockServer = new MockPlaywrightServer();
        $mockTransport = new MockProcessTransport($mockServer);

        // Test initial disconnected state
        $this->assertFalse($mockTransport->isConnected());

        // Connect and verify
        $mockTransport->connect();
        $this->assertTrue($mockTransport->isConnected());
        $this->assertTrue($mockServer->isRunning());

        // Make successful call
        $response = $mockTransport->send([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'Browser.version',
        ]);
        $this->assertEquals('1.40.0', $response['result']['version']);

        // Disconnect transport
        $mockTransport->disconnect();
        $this->assertFalse($mockTransport->isConnected());
        $this->assertFalse($mockServer->isRunning());

        // Attempting calls after disconnect should fail
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transport not connected');
        $mockTransport->send(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'Browser.version']);
    }

    #[Test]
    public function itValidatesJsonRpcMessageStructures(): void
    {
        $mockServer = new MockPlaywrightServer();
        $mockTransport = new MockProcessTransport($mockServer);
        $mockTransport->connect();

        // Make a call and verify JSON-RPC structure
        $testMessage = [
            'jsonrpc' => '2.0',
            'id' => 123,
            'method' => 'Browser.version',
            'params' => ['extra' => 'data'],
        ];

        $response = $mockTransport->send($testMessage);

        // Verify response structure
        $this->assertIsArray($response);
        $this->assertEquals('2.0', $response['jsonrpc']);
        $this->assertEquals(123, $response['id']);
        $this->assertArrayHasKey('result', $response);
        $this->assertEquals('1.40.0', $response['result']['version']);

        // Check sent message was recorded correctly
        $sentMessages = $mockTransport->getSentMessages();
        $this->assertCount(1, $sentMessages);

        $message = $sentMessages[0];
        $this->assertArrayHasKey('jsonrpc', $message);
        $this->assertArrayHasKey('method', $message);
        $this->assertArrayHasKey('id', $message);
        $this->assertArrayHasKey('params', $message);
        $this->assertEquals('2.0', $message['jsonrpc']);
        $this->assertEquals('Browser.version', $message['method']);
        $this->assertEquals(123, $message['id']);
    }

    #[Test]
    public function itHandlesConfigurationWithTransport(): void
    {
        // Test configuration integration with transport layer
        $config = new PlaywrightConfig(
            browser: BrowserType::FIREFOX,
            headless: false,
            nodePath: '/mock/node',
            timeoutMs: 30000,
            slowMoMs: 100
        );

        $this->assertEquals(BrowserType::FIREFOX, $config->browser);
        $this->assertFalse($config->headless);
        $this->assertEquals('/mock/node', $config->nodePath);
        $this->assertEquals(30000, $config->timeoutMs);
        $this->assertEquals(100, $config->slowMoMs);

        // Test that ProcessLauncher can be created with logger
        $launcher = new ProcessLauncher($this->logger);
        $this->assertInstanceOf(ProcessLauncher::class, $launcher);

        // Test that JsonRpcClient can be created with clock
        $clock = new \Symfony\Component\Clock\NativeClock();
        $jsonRpcClient = new JsonRpcClient($clock, $this->logger);
        $this->assertInstanceOf(JsonRpcClient::class, $jsonRpcClient);
    }

    #[Test]
    public function itIntegratesWithRealJsonRpcClient(): void
    {
        // Test that our JsonRpcClient works as expected
        $clock = new \Symfony\Component\Clock\NativeClock();
        $jsonRpcClient = new JsonRpcClient($clock, $this->logger);

        // JsonRpcClient currently has stub implementation, so test that
        $response = $jsonRpcClient->send('test.method', ['param' => 'value']);

        $this->assertIsArray($response);
        $this->assertEquals('ok', $response['status']);
        $this->assertEquals('test.method', $response['method']);

        // Verify logging occurred (JsonRpcClient logs debug messages)
        $this->assertNotEmpty($this->logger->records);

        // Check that some logging occurred
        $hasRelevantLog = false;
        foreach ($this->logger->records as $record) {
            if (str_contains($record['message'], 'JSON-RPC')
                || str_contains($record['message'], 'request')
                || str_contains($record['message'], 'test.method')) {
                $hasRelevantLog = true;
                break;
            }
        }
        $this->assertTrue($hasRelevantLog, 'Expected some JSON-RPC related logging');
    }
}
