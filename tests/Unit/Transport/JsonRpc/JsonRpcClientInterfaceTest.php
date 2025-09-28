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

namespace Playwright\Tests\Unit\Transport\JsonRpc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Tests\Mocks\TestLogger;
use Playwright\Transport\JsonRpc\JsonRpcClient;
use Playwright\Transport\JsonRpc\JsonRpcClientInterface;
use Symfony\Component\Clock\MockClock;

#[CoversClass(JsonRpcClient::class)]
final class JsonRpcClientInterfaceTest extends TestCase
{
    private MockClock $clock;
    private TestLogger $logger;
    private JsonRpcClientInterface $client;

    protected function setUp(): void
    {
        $this->clock = new MockClock();
        $this->logger = new TestLogger();
        $this->client = new TestableJsonRpcClientWithInterface($this->clock, $this->logger);
    }

    public function testSendRawUsesRequestIdFormat(): void
    {
        $client = $this->client;
        assert($client instanceof TestableJsonRpcClientWithInterface);

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

        $this->assertEquals(['requestId' => 1, 'browserId' => 'browser_1', 'status' => 'ok'], $result);
    }

    public function testSendJsonRpcUsesIdFormat(): void
    {
        $client = $this->client;
        assert($client instanceof TestableJsonRpcClientWithInterface);

        $client->setMockResponse(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['status' => 'success']]);

        $result = $client->send('Browser.getVersion', ['param' => 'value']);

        $requests = $client->getSentRequests();
        $this->assertCount(1, $requests);

        $sentRequest = $requests[0];
        $this->assertEquals('Browser.getVersion', $sentRequest['method']);
        $this->assertEquals(['param' => 'value'], $sentRequest['params']);
        $this->assertEquals(1, $sentRequest['id']);
        $this->assertEquals('2.0', $sentRequest['jsonrpc']);
        $this->assertArrayNotHasKey('requestId', $sentRequest);

        $this->assertEquals(['status' => 'success'], $result);
    }

    public function testDualIdSupportInInterface(): void
    {
        $client = $this->client;
        assert($client instanceof TestableJsonRpcClientWithInterface);

        $client->setMockResponse(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['type' => 'jsonrpc']]);
        $result1 = $client->send('test.jsonrpc');
        $this->assertEquals(['type' => 'jsonrpc'], $result1);

        $client->setMockResponse(['requestId' => 2, 'type' => 'raw']);
        $result2 = $client->sendRaw(['action' => 'test']);
        $this->assertEquals(['requestId' => 2, 'type' => 'raw'], $result2);

        $requests = $client->getSentRequests();
        $this->assertCount(2, $requests);

        $this->assertArrayHasKey('id', $requests[0]);
        $this->assertArrayNotHasKey('requestId', $requests[0]);
        $this->assertArrayHasKey('requestId', $requests[1]);
        $this->assertArrayNotHasKey('id', $requests[1]);
    }

    public function testPendingRequestsTracking(): void
    {
        $pending = $this->client->getPendingRequests();
        $this->assertEmpty($pending);

        $this->client->cancelPendingRequests();

        $pendingAfter = $this->client->getPendingRequests();
        $this->assertEmpty($pendingAfter);
    }
}

/**
 * Testable implementation of JsonRpcClientInterface for testing dual ID support.
 */
final class TestableJsonRpcClientWithInterface extends JsonRpcClient
{
    private array $sentRequests = [];
    private ?array $mockResponse = null;

    public function setMockResponse(?array $response): void
    {
        $this->mockResponse = $response;
    }

    public function getSentRequests(): array
    {
        return $this->sentRequests;
    }

    protected function sendAndReceive(array $request, ?float $deadline): array
    {
        $this->sentRequests[] = $request;

        if (null === $this->mockResponse) {
            throw new \RuntimeException('No mock response set');
        }

        return $this->mockResponse;
    }
}
