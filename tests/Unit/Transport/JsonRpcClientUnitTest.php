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

namespace Playwright\Tests\Unit\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\TimeoutException;
use Playwright\Tests\Mocks\TestLogger;
use Playwright\Transport\JsonRpc\JsonRpcClient;
use Symfony\Component\Clock\MockClock;

#[CoversClass(JsonRpcClient::class)]
final class JsonRpcClientUnitTest extends TestCase
{
    public function testGetPendingRequests(): void
    {
        $clock = new MockClock();
        $logger = new TestLogger();
        $client = new TestJsonRpcClient($clock, $logger);

        $pending = $client->getPendingRequests();
        $this->assertIsArray($pending);
        $this->assertEmpty($pending);
    }

    public function testCancelPendingRequests(): void
    {
        $clock = new MockClock();
        $logger = new TestLogger();
        $client = new TestJsonRpcClient($clock, $logger);

        $client->addPendingRequest('req1', function () {});
        $client->addPendingRequest('req2', function () {});

        $this->assertCount(2, $client->getPendingRequests());

        $client->cancelPendingRequests();

        $this->assertEmpty($client->getPendingRequests());
    }

    public function testSendWithTimeout(): void
    {
        $clock = new MockClock();
        $logger = new TestLogger();
        $client = new TimeoutJsonRpcClient($clock, $logger);

        $this->expectException(TimeoutException::class);

        $client->send('test.method', null, 0.1);
    }

    public function testSendSuccess(): void
    {
        $clock = new MockClock();
        $logger = new TestLogger();
        $client = new SuccessJsonRpcClient($clock, $logger);

        $result = $client->send('test.method', ['param' => 'value']);

        $this->assertEquals(['status' => 'success'], $result);
    }

    public function testSendWithParams(): void
    {
        $clock = new MockClock();
        $logger = new TestLogger();
        $client = new EchoJsonRpcClient($clock, $logger);

        $params = ['key' => 'value', 'number' => 42];
        $result = $client->send('echo.method', $params);

        $this->assertEquals($params, $result['params']);
    }
}

class TestJsonRpcClient extends JsonRpcClient
{
    public array $mockPendingRequests = [];

    public function addPendingRequest(string $id, callable $callback): void
    {
        $this->mockPendingRequests[$id] = $callback;
    }

    public function getPendingRequests(): array
    {
        return $this->mockPendingRequests;
    }

    public function cancelPendingRequests(): void
    {
        $this->mockPendingRequests = [];
        parent::cancelPendingRequests();
    }

    protected function sendAndReceive(array $request, ?float $deadline): array
    {
        return ['id' => $request['id'], 'result' => 'test'];
    }
}

class TimeoutJsonRpcClient extends JsonRpcClient
{
    protected function sendAndReceive(array $request, ?float $deadline): array
    {
        throw new TimeoutException('Request timed out');
    }
}

class SuccessJsonRpcClient extends JsonRpcClient
{
    protected function sendAndReceive(array $request, ?float $deadline): array
    {
        return ['id' => $request['id'], 'result' => ['status' => 'success']];
    }
}

class EchoJsonRpcClient extends JsonRpcClient
{
    protected function sendAndReceive(array $request, ?float $deadline): array
    {
        return ['id' => $request['id'], 'result' => ['params' => $request['params'] ?? []]];
    }
}
