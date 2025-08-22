<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Transport\JsonRpc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Exception\DisconnectedException;
use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Tests\Mocks\TestLogger;
use PlaywrightPHP\Transport\JsonRpc\JsonRpcClientInterface;
use PlaywrightPHP\Transport\JsonRpc\ProcessJsonRpcClient;
use PlaywrightPHP\Transport\JsonRpc\ProcessLauncherInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

#[CoversClass(ProcessJsonRpcClient::class)]
final class ProcessJsonRpcClientTest extends TestCase
{
    private MockClock $clock;
    private TestLogger $logger;
    private Process $mockProcess;
    private ProcessLauncherInterface $mockProcessLauncher;
    private InputStream $mockInputStream;
    private ProcessJsonRpcClient $client;

    protected function setUp(): void
    {
        $this->clock = new MockClock();
        $this->logger = new TestLogger();
        $this->mockProcess = $this->createMock(Process::class);
        $this->mockProcessLauncher = $this->createMock(ProcessLauncherInterface::class);
        $this->mockInputStream = $this->createMock(InputStream::class);

        // Setup basic mocks
        $this->mockProcess->method('isRunning')->willReturn(true);
        $this->mockProcess->method('getPid')->willReturn(12345);
        $this->mockProcessLauncher->method('getInputStream')->willReturn($this->mockInputStream);
        $this->mockProcessLauncher->method('ensureRunning');

        $this->client = new ProcessJsonRpcClient(
            process: $this->mockProcess,
            processLauncher: $this->mockProcessLauncher,
            clock: $this->clock,
            logger: $this->logger,
            defaultTimeoutMs: 1000.0
        );
    }

    public function testConstructorRequiresInputStream(): void
    {
        $mockProcessLauncherWithoutStream = $this->createMock(ProcessLauncherInterface::class);
        $mockProcessLauncherWithoutStream
            ->method('getInputStream')
            ->willReturn(null);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('ProcessLauncher must have an InputStream for JSON-RPC communication');

        new ProcessJsonRpcClient(
            process: $this->mockProcess,
            processLauncher: $mockProcessLauncherWithoutStream,
            clock: $this->clock,
            logger: $this->logger
        );
    }

    public function testProcessJsonRpcClientCreationSucceedsWithInputStream(): void
    {
        $client = new ProcessJsonRpcClient(
            process: $this->mockProcess,
            processLauncher: $this->mockProcessLauncher,
            clock: $this->clock,
            logger: $this->logger
        );

        $this->assertInstanceOf(ProcessJsonRpcClient::class, $client);
    }

    public function testImplementsJsonRpcClientInterface(): void
    {
        $this->assertInstanceOf(JsonRpcClientInterface::class, $this->client);
    }

    public function testProcessNotRunningThrowsDisconnectedException(): void
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcessLauncher = $this->createMock(ProcessLauncherInterface::class);
        $mockInputStream = $this->createMock(InputStream::class);
        
        $mockProcess->method('isRunning')->willReturn(false);
        $mockProcess->method('getExitCode')->willReturn(1);
        $mockProcess->method('getPid')->willReturn(12345);
        $mockProcessLauncher->method('getInputStream')->willReturn($mockInputStream);
        
        $client = new ProcessJsonRpcClient(
            process: $mockProcess,
            processLauncher: $mockProcessLauncher,
            clock: $this->clock,
            logger: $this->logger
        );
        
        $this->expectException(DisconnectedException::class);
        $this->expectExceptionMessage('Process exited with code 1');
        
        $client->send('test.method');
    }

    public function testInputStreamWriteFailureThrowsNetworkException(): void
    {
        $mockInputStream = $this->createMock(InputStream::class);
        $mockInputStream
            ->method('write')
            ->willThrowException(new \RuntimeException('Write failed'));
        
        $mockProcess = $this->createMock(Process::class);
        $mockProcessLauncher = $this->createMock(ProcessLauncherInterface::class);
        
        $mockProcess->method('isRunning')->willReturn(true);
        $mockProcess->method('getPid')->willReturn(12345);
        $mockProcessLauncher->method('getInputStream')->willReturn($mockInputStream);
        $mockProcessLauncher->method('ensureRunning');
        
        $client = new ProcessJsonRpcClient(
            process: $mockProcess,
            processLauncher: $mockProcessLauncher,
            clock: $this->clock,
            logger: $this->logger
        );
        
        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to write to process stdin: Write failed');
        
        $client->send('test.method');
    }
}
