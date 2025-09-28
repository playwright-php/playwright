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
use Playwright\Tests\Mocks\TestLogger;
use Playwright\Transport\JsonRpc\JsonRpcClient;
use Symfony\Component\Clock\Clock;

#[CoversClass(JsonRpcClient::class)]
class JsonRpcClientTest extends TestCase
{
    #[Test]
    public function itCanBeInstantiated(): void
    {
        $clock = Clock::get();
        $client = new JsonRpcClient($clock);

        $this->assertInstanceOf(JsonRpcClient::class, $client);
    }

    #[Test]
    public function itCanSendBasicRequest(): void
    {
        $clock = Clock::get();
        $logger = new TestLogger();
        $client = new JsonRpcClient($clock, $logger);

        $result = $client->send('test.method');

        $this->assertIsArray($result);
        $this->assertEquals('ok', $result['status']);
        $this->assertEquals('test.method', $result['method']);

        $this->assertGreaterThan(0, count($logger->records));
    }

    #[Test]
    public function itCanSendRequestWithParams(): void
    {
        $clock = Clock::get();
        $client = new JsonRpcClient($clock);

        $params = ['param1' => 'value1', 'param2' => 42];
        $result = $client->send('test.method', $params);

        $this->assertIsArray($result);
        $this->assertEquals('ok', $result['status']);
    }

    #[Test]
    public function itCanSendRequestWithCustomTimeout(): void
    {
        $clock = Clock::get();
        $client = new JsonRpcClient($clock);

        $result = $client->send('test.method', null, 5000.0);

        $this->assertIsArray($result);
    }

    #[Test]
    public function itTracksAndClearsPendingRequests(): void
    {
        $clock = Clock::get();
        $client = new JsonRpcClient($clock);

        $this->assertEmpty($client->getPendingRequests());

        $client->cancelPendingRequests();
        $this->assertEmpty($client->getPendingRequests());
    }

    #[Test]
    public function itUsesDefaultTimeout(): void
    {
        $clock = Clock::get();
        $client = new JsonRpcClient($clock, defaultTimeoutMs: 10000.0);

        $result = $client->send('test.method');

        $this->assertIsArray($result);
    }

    #[Test]
    public function itLogsRequestsWhenLoggerProvided(): void
    {
        $clock = Clock::get();
        $logger = new TestLogger();
        $client = new JsonRpcClient($clock, $logger);

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
        $clock = Clock::get();
        $logger = new TestLogger();
        $client = new JsonRpcClient($clock, $logger);

        $client->send('method1');
        $client->send('method2');
        $client->send('method3');

        $this->assertGreaterThanOrEqual(3, count($logger->records));
    }

    #[Test]
    public function itHandlesZeroTimeout(): void
    {
        $clock = Clock::get();
        $client = new JsonRpcClient($clock);

        $result = $client->send('test.method', null, 0.0);

        $this->assertIsArray($result);
    }

    #[Test]
    public function itHandlesNegativeTimeout(): void
    {
        $clock = Clock::get();
        $client = new JsonRpcClient($clock);

        $result = $client->send('test.method', null, -1.0);

        $this->assertIsArray($result);
    }
}
