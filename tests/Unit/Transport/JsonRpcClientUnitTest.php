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

        // Add some pending requests via mock
        $client->addPendingRequest('req1', function () {});
        $client->addPendingRequest('req2', function () {});

        // Verify they exist
        $this->assertCount(2, $client->getPendingRequests());

        // Cancel all
        $client->cancelPendingRequests();

        // Should be empty
        $this->assertEmpty($client->getPendingRequests());
    }

    public function testSendWithTimeout(): void
    {
        $clock = new MockClock();
        $logger = new TestLogger();
        $client = new TimeoutJsonRpcClient($clock, $logger);

        $this->expectException(TimeoutException::class);

        $client->send('test.method', null, 0.1); // 100ms timeout
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
        // Simulate timeout
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
