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
use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Tests\Mocks\TestLogger;
use PlaywrightPHP\Transport\JsonRpc\JsonRpcTransport;
use PlaywrightPHP\Transport\JsonRpc\ProcessLauncherInterface;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

#[CoversClass(JsonRpcTransport::class)]
final class JsonRpcTransportTest extends TestCase
{
    private ProcessLauncherInterface $processLauncher;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->processLauncher = $this->createMock(ProcessLauncherInterface::class);
        $this->logger = new TestLogger();
    }

    public function testConnectStartsProcess(): void
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->method('isRunning')->willReturn(true);
        $mockProcess->method('getPid')->willReturn(12345);

        $mockInputStream = $this->createMock(InputStream::class);

        $this->processLauncher
            ->expects($this->once())
            ->method('start')
            ->willReturn($mockProcess);

        $this->processLauncher
            ->method('getInputStream')
            ->willReturn($mockInputStream);

        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            config: ['command' => ['node', 'server.js']],
            logger: $this->logger
        );

        $transport->connect();

        $this->assertTrue($transport->isConnected());
        $this->assertTrue($this->logger->hasInfoRecords());
    }

    public function testConnectFailsWhenProcessLauncherThrows(): void
    {
        $this->processLauncher
            ->expects($this->once())
            ->method('start')
            ->willThrowException(new \RuntimeException('Failed to start process'));

        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            logger: $this->logger
        );

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to connect JSON-RPC transport');

        $transport->connect();
    }

    public function testIsConnectedReturnsFalseWhenNotConnected(): void
    {
        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            logger: $this->logger
        );

        $this->assertFalse($transport->isConnected());
    }

    public function testIsConnectedReturnsFalseWhenProcessDied(): void
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->method('isRunning')->willReturn(false);
        $mockProcess->method('getPid')->willReturn(12345);

        $mockInputStream = $this->createMock(InputStream::class);

        $this->processLauncher
            ->method('start')
            ->willReturn($mockProcess);

        $this->processLauncher
            ->method('getInputStream')
            ->willReturn($mockInputStream);

        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            logger: $this->logger
        );

        $transport->connect();
        $this->assertFalse($transport->isConnected());
    }

    public function testDisconnectStopsProcess(): void
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->method('isRunning')->willReturn(true);
        $mockProcess->method('getPid')->willReturn(12345);

        $mockProcess->expects($this->once())
            ->method('stop');

        $mockInputStream = $this->createMock(InputStream::class);

        $this->processLauncher
            ->method('start')
            ->willReturn($mockProcess);

        $this->processLauncher
            ->method('getInputStream')
            ->willReturn($mockInputStream);

        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            logger: $this->logger
        );

        $transport->connect();
        $transport->disconnect();

        $this->assertFalse($transport->isConnected());
    }

    public function testSendThrowsWhenNotConnected(): void
    {
        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            logger: $this->logger
        );

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('JSON-RPC transport not connected');

        $transport->send(['action' => 'test']);
    }

    public function testSendAsyncDoesNotThrowWhenNotConnected(): void
    {
        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            logger: $this->logger
        );

        // Should log warning but not throw
        $transport->sendAsync(['action' => 'test']);

        $this->assertTrue($this->logger->hasWarningRecords());
    }

    public function testProcessEventsIsNoOpButDoesNotThrow(): void
    {
        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            logger: $this->logger
        );

        // Should not throw even when not connected
        $transport->processEvents();

        $this->assertTrue(true); // Test passes if we get here
    }

    public function testDestructorDisconnects(): void
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->method('isRunning')->willReturn(true);
        $mockProcess->method('getPid')->willReturn(12345);

        $mockProcess->expects($this->once())
            ->method('stop');

        $mockInputStream = $this->createMock(InputStream::class);

        $this->processLauncher
            ->method('start')
            ->willReturn($mockProcess);

        $this->processLauncher
            ->method('getInputStream')
            ->willReturn($mockInputStream);

        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            logger: $this->logger
        );

        $transport->connect();
        unset($transport); // Trigger destructor

        // If we get here without hanging, the destructor worked
        $this->assertTrue(true);
    }
}
