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

namespace Playwright\Tests\Integration\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Tests\Mocks\FixedClock;
use Playwright\Tests\Mocks\TestLogger;
use Playwright\Transport\JsonRpc\JsonRpcClient;

#[CoversClass(JsonRpcClient::class)]
class JsonRpcClientTest extends TestCase
{
    #[Test]
    public function itCanBeInstantiated(): void
    {
        $client = new JsonRpcClient(new FixedClock());

        $this->assertInstanceOf(JsonRpcClient::class, $client);
    }

    #[Test]
    public function itCanSendBasicRequest(): void
    {
        $logger = new TestLogger();
        $client = new JsonRpcClient(new FixedClock(), $logger);

        $result = $client->send('test.method');

        $this->assertIsArray($result);
        $this->assertEquals('ok', $result['status']);
        $this->assertEquals('test.method', $result['method']);

        $this->assertGreaterThan(0, count($logger->records));
    }

    #[Test]
    public function itCanSendRequestWithParams(): void
    {
        $client = new JsonRpcClient(new FixedClock());

        $params = ['param1' => 'value1', 'param2' => 42];
        $result = $client->send('test.method', $params);

        $this->assertIsArray($result);
        $this->assertEquals('ok', $result['status']);
    }

    #[Test]
    public function itCanSendRequestWithCustomTimeout(): void
    {
        $client = new JsonRpcClient(new FixedClock());

        $result = $client->send('test.method', null, 5000.0);

        $this->assertIsArray($result);
    }

    #[Test]
    public function itTracksAndClearsPendingRequests(): void
    {
        $client = new JsonRpcClient(new FixedClock());

        $this->assertEmpty($client->getPendingRequests());

        $client->cancelPendingRequests();
        $this->assertEmpty($client->getPendingRequests());
    }

    #[Test]
    public function itUsesDefaultTimeout(): void
    {
        $client = new JsonRpcClient(new FixedClock(), defaultTimeoutMs: 10000.0);

        $result = $client->send('test.method');

        $this->assertIsArray($result);
    }

    #[Test]
    public function itLogsRequestsWhenLoggerProvided(): void
    {
        $logger = new TestLogger();
        $client = new JsonRpcClient(new FixedClock(), $logger);

        $client->send('test.method', ['key' => 'value']);

        $this->assertGreaterThan(0, count($logger->records));

        $debugMessages = array_filter($logger->records, fn ($record) => 'debug' === $record['level']);
        $this->assertNotEmpty($debugMessages);

        $message = reset($debugMessages)['message'];
        $this->assertStringContainsString('Sending JSON-RPC request', $message);
    }

    #[Test]
    public function itIncrementRequestIds(): void
    {
        $logger = new TestLogger();
        $client = new JsonRpcClient(new FixedClock(), $logger);

        $client->send('method1');
        $client->send('method2');
        $client->send('method3');

        $this->assertGreaterThanOrEqual(3, count($logger->records));
    }

    #[Test]
    public function itHandlesZeroTimeout(): void
    {
        $client = new JsonRpcClient(new FixedClock());

        $result = $client->send('test.method', null, 0.0);

        $this->assertIsArray($result);
    }

    #[Test]
    public function itHandlesNegativeTimeout(): void
    {
        $client = new JsonRpcClient(new FixedClock());

        $result = $client->send('test.method', null, -1.0);

        $this->assertIsArray($result);
    }
}
