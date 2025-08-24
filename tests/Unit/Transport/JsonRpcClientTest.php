<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Exception\TimeoutException;
use PlaywrightPHP\Tests\Mocks\TestLogger;
use PlaywrightPHP\Transport\JsonRpc\JsonRpcClient;
use Symfony\Component\Clock\MockClock;

#[CoversClass(JsonRpcClient::class)]
final class JsonRpcClientTest extends TestCase
{
    private MockClock $clock;
    private TestLogger $logger;
    private JsonRpcClient $client;

    protected function setUp(): void
    {
        $this->clock = new MockClock();
        $this->logger = new TestLogger();
        $this->client = new JsonRpcClient(
            clock: $this->clock,
            logger: $this->logger,
            defaultTimeoutMs: 5000.0
        );
    }

    public function testSendGeneratesUniqueCorrelationIds(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger);

        $client->setMockResponse(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['status' => 'ok']]);
        $result1 = $client->send('method1');

        $client->setMockResponse(['jsonrpc' => '2.0', 'id' => 2, 'result' => ['status' => 'ok']]);
        $result2 = $client->send('method2');

        $requests = $client->getSentRequests();

        $this->assertCount(2, $requests);
        $this->assertEquals(1, $requests[0]['id']);
        $this->assertEquals(2, $requests[1]['id']);
        $this->assertEquals('method1', $requests[0]['method']);
        $this->assertEquals('method2', $requests[1]['method']);
    }

    public function testSendTracksPendingRequests(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger);

        $client->setResponseDelay(100);
        $client->setMockResponse(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['status' => 'ok']]);

        $pendingBefore = $client->getPendingRequests();
        $this->assertEmpty($pendingBefore);

        $startTime = $this->clock->now()->format('Uu') / 1000;

        $result = $client->send('test_method');

        $pendingAfter = $client->getPendingRequests();
        $this->assertEmpty($pendingAfter);

        $this->assertEquals(['status' => 'ok'], $result);
    }

    public function testSendHandlesTimeout(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger, 100.0);

        $client->setMockResponse(null);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Mock timeout exceeded deadline');

        $client->send('test_method');
    }

    public function testSendHandlesProtocolError(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger);

        $client->setMockResponse([
            'jsonrpc' => '2.0',
            'id' => 1,
            'error' => [
                'name' => 'TimeoutError',
                'message' => 'Operation timed out',
                'code' => 408,
            ],
        ]);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Operation timed out');

        $client->send('slow_method', ['param' => 'value'], 1000.0);
    }

    public function testCancelPendingRequests(): void
    {
        $pending = $this->client->getPendingRequests();
        $this->assertEmpty($pending);

        $this->client->cancelPendingRequests();

        $pendingAfter = $this->client->getPendingRequests();
        $this->assertEmpty($pendingAfter);
    }

    public function testLogsDebugInformation(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger);
        $client->setMockResponse(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['data' => 'test']]);

        $client->send('test_method', ['param1' => 'value1', 'sensitive_password' => 'secret']);

        $this->assertTrue($this->logger->hasDebugRecords());

        $debugRecords = $this->logger->records;
        $sendRecord = null;
        foreach ($debugRecords as $record) {
            if (str_contains($record['message'], 'Sending JSON-RPC request')) {
                $sendRecord = $record;
                break;
            }
        }

        $this->assertNotNull($sendRecord);
        $this->assertEquals('test_method', $sendRecord['context']['method']);
        $this->assertEquals(['param1', 'sensitive_password'], $sendRecord['context']['params']);
        $this->assertEquals(30000.0, $sendRecord['context']['timeoutMs']);
    }

    public function testUsesDefaultTimeout(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger, 2000.0);
        $client->setMockResponse(['jsonrpc' => '2.0', 'id' => 1, 'result' => []]);

        $client->send('test_method');

        $debugRecords = $this->logger->records;
        $sendRecord = null;
        foreach ($debugRecords as $record) {
            if (str_contains($record['message'], 'Sending JSON-RPC request')) {
                $sendRecord = $record;
                break;
            }
        }

        $this->assertNotNull($sendRecord);
        $this->assertEquals(2000.0, $sendRecord['context']['timeoutMs']);
    }

    public function testOverrideTimeout(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger, 2000.0);
        $client->setMockResponse(['jsonrpc' => '2.0', 'id' => 1, 'result' => []]);

        $client->send('test_method', null, 7000.0);

        $debugRecords = $this->logger->records;
        $sendRecord = null;
        foreach ($debugRecords as $record) {
            if (str_contains($record['message'], 'Sending JSON-RPC request')) {
                $sendRecord = $record;
                break;
            }
        }

        $this->assertNotNull($sendRecord);
        $this->assertEquals(7000.0, $sendRecord['context']['timeoutMs']);
    }

    public function testSendRawUsesRequestIdInsteadOfId(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger);
        $client->setMockResponse(['requestId' => 1, 'browserId' => 'browser_1', 'status' => 'ok']);

        $message = ['action' => 'launch', 'browser' => 'chromium', 'options' => ['headless' => true]];
        $result = $client->sendRaw($message);

        $requests = $client->getSentRequests();
        $this->assertCount(1, $requests);

        $sentRequest = $requests[0];
        $this->assertEquals('launch', $sentRequest['action']);
        $this->assertEquals('chromium', $sentRequest['browser']);
        $this->assertEquals(['headless' => true], $sentRequest['options']);
        $this->assertEquals(1, $sentRequest['requestId']);
        $this->assertArrayNotHasKey('id', $sentRequest);
        $this->assertArrayNotHasKey('jsonrpc', $sentRequest);

        $this->assertEquals(['browserId' => 'browser_1', 'status' => 'ok', 'requestId' => 1], $result);
    }

    public function testSendRawLogsWithActionInsteadOfMethod(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger);
        $client->setMockResponse(['requestId' => 1, 'status' => 'ok']);

        $message = ['action' => 'newContext', 'browserId' => 'browser_1'];
        $client->sendRaw($message);

        $this->assertTrue($this->logger->hasDebugRecords());

        $debugRecords = $this->logger->records;
        $sendRecord = null;
        foreach ($debugRecords as $record) {
            if (str_contains($record['message'], 'Sending raw request')) {
                $sendRecord = $record;
                break;
            }
        }

        $this->assertNotNull($sendRecord);
        $this->assertEquals('newContext', $sendRecord['context']['action']);
        $this->assertEquals(30000.0, $sendRecord['context']['timeoutMs']);
    }

    public function testSendRawHandlesTimeoutWithAction(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger, 100.0);
        $client->setMockResponse(null);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Mock timeout exceeded deadline');

        $client->sendRaw(['action' => 'slow_action']);
    }

    public function testSendRawGeneratesUniqueRequestIds(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger);

        $client->setMockResponse(['requestId' => 1, 'status' => 'ok']);
        $client->sendRaw(['action' => 'action1']);

        $client->setMockResponse(['requestId' => 2, 'status' => 'ok']);
        $client->sendRaw(['action' => 'action2']);

        $requests = $client->getSentRequests();
        $this->assertCount(2, $requests);
        $this->assertEquals(1, $requests[0]['requestId']);
        $this->assertEquals(2, $requests[1]['requestId']);
        $this->assertEquals('action1', $requests[0]['action']);
        $this->assertEquals('action2', $requests[1]['action']);
    }

    public function testSendRawReturnsResponseDirectly(): void
    {
        $client = new TestableJsonRpcClient($this->clock, $this->logger);

        $mockResponse = [
            'requestId' => 1,
            'browserId' => 'browser_1',
            'defaultContextId' => 'context_1',
            'version' => '1.0.0',
        ];
        $client->setMockResponse($mockResponse);

        $result = $client->sendRaw(['action' => 'launch']);

        $this->assertEquals($mockResponse, $result);
    }
}

/**
 * Testable version of JsonRpcClient that allows mocking responses.
 */
class TestableJsonRpcClient extends JsonRpcClient
{
    private array $sentRequests = [];
    private ?array $mockResponse = null;
    private int $responseDelay = 0;

    public function setMockResponse(?array $response): void
    {
        $this->mockResponse = $response;
    }

    public function setResponseDelay(int $delayMs): void
    {
        $this->responseDelay = $delayMs;
    }

    public function getSentRequests(): array
    {
        return $this->sentRequests;
    }

    protected function sendAndReceive(array $request, ?float $deadline): array
    {
        $this->sentRequests[] = $request;

        if ($this->responseDelay > 0) {
            usleep($this->responseDelay * 1000);
        }

        if (null === $this->mockResponse) {
            if (null !== $deadline) {
                throw new TimeoutException('Mock timeout exceeded deadline', 100.0);
            }
            throw new \RuntimeException('No mock response set');
        }

        return $this->mockResponse;
    }

    public function getCurrentTimeMs(): float
    {
        return parent::getCurrentTimeMs();
    }
}
