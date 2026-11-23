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
use Playwright\Exception\DisconnectedException;
use Playwright\Exception\MissingDependencyException;
use Playwright\Exception\NetworkException;
use Playwright\Exception\ProcessCrashedException;
use Playwright\Exception\TimeoutException;
use Playwright\Tests\Mocks\FixedClock;
use Playwright\Tests\Mocks\TestLogger;
use Playwright\Transport\JsonRpc\JsonRpcClientInterface;
use Playwright\Transport\JsonRpc\LspFraming;
use Playwright\Transport\JsonRpc\ProcessJsonRpcClient;
use Playwright\Transport\JsonRpc\ProcessLauncherInterface;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

#[CoversClass(ProcessJsonRpcClient::class)]
final class ProcessJsonRpcClientTest extends TestCase
{
    private const READY = "Content-Length: 34\r\n\r\n{\"type\":\"ready\",\"message\":\"READY\"}";
    private const RESPONSE = "Content-Length: 30\r\n\r\n{\"requestId\":1,\"value\":\"done\"}";

    private FixedClock $clock;
    private TestLogger $logger;
    private Process $mockProcess;
    private ProcessLauncherInterface $mockProcessLauncher;
    private InputStream $mockInputStream;
    private ProcessJsonRpcClient $client;

    protected function setUp(): void
    {
        $this->clock = new FixedClock();
        $this->logger = new TestLogger();
        $this->mockProcess = $this->createMock(Process::class);
        $this->mockProcessLauncher = $this->createMock(ProcessLauncherInterface::class);
        $this->mockInputStream = $this->createMock(InputStream::class);

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
        $mockProcessLauncher->method('getStderrOutput')->willReturn('Startup failed with Bearer example-credential');

        $client = new ProcessJsonRpcClient(
            process: $mockProcess,
            processLauncher: $mockProcessLauncher,
            clock: $this->clock,
            logger: $this->logger
        );

        $this->expectException(DisconnectedException::class);
        $this->expectExceptionMessage("Process exited with code 1:\n\nStartup failed with Bearer [REDACTED]");

        $client->send('test.method');
    }

    public function testProcessExitWithoutPlaywrightModuleThrowsMissingDependencyException(): void
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcessLauncher = $this->createMock(ProcessLauncherInterface::class);

        $mockProcess->method('isRunning')->willReturn(false);
        $mockProcess->method('getExitCode')->willReturn(1);
        $mockProcessLauncher->method('getInputStream')->willReturn($this->createMock(InputStream::class));
        $mockProcessLauncher->method('getStderrOutput')->willReturn("Error: Cannot find module 'playwright'");

        $client = new ProcessJsonRpcClient(
            process: $mockProcess,
            processLauncher: $mockProcessLauncher,
            clock: $this->clock,
            logger: $this->logger
        );

        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage(MissingDependencyException::INSTALL_COMMAND);

        $client->send('test.method');
    }

    public function testCrashedProcessThrowsDisconnectedExceptionWithStderr(): void
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcessLauncher = $this->createMock(ProcessLauncherInterface::class);

        $mockProcess->method('isRunning')->willReturn(true);
        $mockProcess->method('getExitCode')->willReturn(134);
        $mockProcessLauncher->method('getInputStream')->willReturn($this->createMock(InputStream::class));
        $mockProcessLauncher->method('ensureRunning')->willThrowException(new ProcessCrashedException('Process crashed', 134, 'Aborted'));
        $mockProcessLauncher->method('getStderrOutput')->willReturn('Aborted');

        $client = new ProcessJsonRpcClient(
            process: $mockProcess,
            processLauncher: $mockProcessLauncher,
            clock: $this->clock,
            logger: $this->logger
        );

        $this->expectException(DisconnectedException::class);
        $this->expectExceptionMessage("Process exited with code 134:\n\nAborted");

        $client->send('test.method');
    }

    public function testProcessStderrIsLoggedSanitized(): void
    {
        $this->mockProcess->method('getIncrementalOutput')->willReturn(LspFraming::encode('{"requestId":1,"value":"ok"}'));
        $this->mockProcess->method('getIncrementalErrorOutput')->willReturn('Authorization: Bearer example-credential');

        $this->client->sendRaw(['action' => 'page.url', 'requestId' => 1]);

        $warnings = array_values(array_filter($this->logger->records, static fn (array $record): bool => 'warning' === $record['level']));
        $this->assertCount(1, $warnings);
        $this->assertSame('Process stderr', $warnings[0]['message']);
        $this->assertStringNotContainsString('example-credential', $warnings[0]['context']['stderr']);
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

    public function testTimedOutRequestThrowsItsHandlerExceptionWithoutAffectingTheNextRequest(): void
    {
        $failure = new \RuntimeException('Handler failed');
        $this->client->setEventHandler(function () use ($failure): void {
            $this->clock->advanceMilliseconds(1001);

            throw $failure;
        });
        $this->mockProcess->method('getIncrementalOutput')->willReturnOnConsecutiveCalls(
            LspFraming::encode('{"event":"route"}'),
            LspFraming::encode('{"requestId":2,"value":"about:blank"}'),
        );

        try {
            $this->client->sendRaw(['action' => 'page.evaluate', 'requestId' => 1]);
            $this->fail('Expected the handler exception.');
        } catch (\Throwable $exception) {
            $this->assertSame($failure, $exception);
        }

        $response = $this->client->sendRaw(['action' => 'page.url', 'requestId' => 2]);

        $this->assertSame('about:blank', $response['value']);
    }

    public function testASilentServerThrowsATimeoutExceptionForARequestWithoutDeadline(): void
    {
        $client = $this->clientReading([[0, self::READY]]);

        try {
            $client->sendRaw(['action' => 'locator.click', 'requestId' => 1], 0.0);
            $this->fail('A silent server must end the request.');
        } catch (TimeoutException $exception) {
            $this->assertSame('The Playwright server stopped responding: no output for 2500ms while waiting for request 1.', $exception->getMessage());
            $this->assertSame(2000.0, $exception->getTimeoutMs());
        }
    }

    public function testHeartbeatsKeepARequestWaitingPastTheIdleTimeout(): void
    {
        $heartbeat = LspFraming::encode('{"type":"heartbeat"}');
        $client = $this->clientReading([...array_fill(0, 20, [500, $heartbeat]), [500, self::RESPONSE]]);

        $response = $client->sendRaw(['action' => 'locator.click', 'requestId' => 1], 0.0);

        $this->assertSame('done', $response['value']);
        $this->assertSame([], array_filter($this->logger->records, static fn (array $record): bool => 'warning' === $record['level']));
    }

    public function testSilenceIsCountedFromTheLastOutputWhileACallRuns(): void
    {
        $polls = 0;
        $event = LspFraming::encode('{"event":"console"}');
        $client = $this->clientReading([[0, self::READY], [500, ''], [500, ''], [500, $event]], polls: $polls);

        try {
            $client->sendRaw(['action' => 'locator.click', 'requestId' => 1], 0.0);
            $this->fail('A server silent since its last output must end the request.');
        } catch (TimeoutException $exception) {
            // the event at 1500 ms restarts the count: the request ends at 4000 ms, not at 2500 ms
            $this->assertStringContainsString('no output for 2500ms', $exception->getMessage());
            $this->assertSame(9, $polls);
        }
    }

    public function testIdleTimeBetweenTwoCallsIsNotCounted(): void
    {
        $client = $this->clientReading([[0, self::READY.self::RESPONSE], [500, LspFraming::encode('{"requestId":2,"value":"again"}')]]);
        $client->sendRaw(['action' => 'page.url', 'requestId' => 1], 0.0);

        $this->clock->advanceMilliseconds(60000);
        $response = $client->sendRaw(['action' => 'page.url', 'requestId' => 2], 0.0);

        $this->assertSame('again', $response['value']);
    }

    public function testAServerThatNeverStartsThrowsATimeoutException(): void
    {
        $client = $this->clientReading([]);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('The Playwright server stopped responding: no output for 2500ms');

        $client->sendRaw(['action' => 'launch', 'requestId' => 1], 0.0);
    }

    public function testTimeDuringWhichPhpWasNotPollingIsNotCountedAsSilence(): void
    {
        $client = $this->clientReading([[0, self::READY], [60000, ''], [500, self::RESPONSE]]);

        $response = $client->sendRaw(['action' => 'locator.click', 'requestId' => 1], 0.0);

        $this->assertSame('done', $response['value']);
    }

    public function testSilenceObservedBeforeAPauseOfPhpStillCounts(): void
    {
        $polls = 0;
        $client = $this->clientReading([[0, self::READY], [500, ''], [500, ''], [500, ''], [60000, '']], polls: $polls);

        try {
            $client->sendRaw(['action' => 'locator.click', 'requestId' => 1], 0.0);
            $this->fail('A silent server must end the request.');
        } catch (TimeoutException $exception) {
            $this->assertStringContainsString('no output for 2500ms', $exception->getMessage());
            $this->assertSame(7, $polls);
        }
    }

    public function testAZeroIdleTimeoutDisablesTheCheck(): void
    {
        $client = $this->clientReading([[0, self::READY], ...array_fill(0, 20, [500, '']), [500, self::RESPONSE]], 0.0);

        $response = $client->sendRaw(['action' => 'locator.click', 'requestId' => 1], 0.0);

        $this->assertSame('done', $response['value']);
    }

    /**
     * @param list<array{int, string}> $reads each poll of the server output: the time it takes
     *                                        in milliseconds, then what it returns; once the
     *                                        list is used up, a poll takes 500 ms and returns nothing
     */
    private function clientReading(array $reads, float $idleTimeoutMs = 2000.0, int &$polls = 0): ProcessJsonRpcClient
    {
        $process = $this->createMock(Process::class);
        $process->method('isRunning')->willReturn(true);
        $process->method('getPid')->willReturn(12345);
        $process->method('getIncrementalErrorOutput')->willReturn('');
        $process->method('getIncrementalOutput')->willReturnCallback(function () use (&$reads, &$polls): string {
            ++$polls;
            [$milliseconds, $output] = array_shift($reads) ?? [500, ''];
            $this->clock->advanceMilliseconds($milliseconds);

            return $output;
        });

        return new ProcessJsonRpcClient(
            process: $process,
            processLauncher: $this->mockProcessLauncher,
            clock: $this->clock,
            logger: $this->logger,
            idleTimeoutMs: $idleTimeoutMs,
        );
    }

    public function testReadProcessOutputClearsBothProcessBuffers(): void
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcessLauncher = $this->createMock(ProcessLauncherInterface::class);

        $mockProcess->method('isRunning')->willReturn(true);
        $mockProcess->method('getPid')->willReturn(12345);
        $mockProcess->method('getIncrementalOutput')->willReturn('');
        $mockProcess->method('getIncrementalErrorOutput')->willReturn('');
        $mockProcess->expects($this->once())->method('clearOutput');
        $mockProcess->expects($this->once())->method('clearErrorOutput');
        $mockProcessLauncher->method('getInputStream')->willReturn($this->mockInputStream);
        $mockProcessLauncher->method('ensureRunning');

        $client = new ProcessJsonRpcClient(
            process: $mockProcess,
            processLauncher: $mockProcessLauncher,
            clock: $this->clock,
            logger: $this->logger
        );

        $readProcessOutput = new \ReflectionMethod($client, 'readProcessOutput');
        $readProcessOutput->invoke($client);
    }

    public function testReadProcessOutputClearsBuffersAcrossMultiplePolls(): void
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcessLauncher = $this->createMock(ProcessLauncherInterface::class);

        $mockProcess->method('isRunning')->willReturn(true);
        $mockProcess->method('getPid')->willReturn(12345);
        $mockProcess->method('getIncrementalOutput')->willReturn('');
        $mockProcess->method('getIncrementalErrorOutput')->willReturn('');
        $mockProcess->expects($this->exactly(3))->method('clearOutput');
        $mockProcess->expects($this->exactly(3))->method('clearErrorOutput');
        $mockProcessLauncher->method('getInputStream')->willReturn($this->mockInputStream);
        $mockProcessLauncher->method('ensureRunning');

        $client = new ProcessJsonRpcClient(
            process: $mockProcess,
            processLauncher: $mockProcessLauncher,
            clock: $this->clock,
            logger: $this->logger
        );

        $readProcessOutput = new \ReflectionMethod($client, 'readProcessOutput');
        $readProcessOutput->invoke($client);
        $readProcessOutput->invoke($client);
        $readProcessOutput->invoke($client);
    }

    public function testNestedRequestsConsumeQueuedEventsExactlyOnce(): void
    {
        $calls = 0;
        $nested = null;
        $this->client->setEventHandler(function () use (&$calls, &$nested): void {
            ++$calls;

            if (1 === $calls) {
                $nested = $this->client->sendRaw(['action' => 'page.title', 'requestId' => 2]);
            }
        });
        $this->mockProcess->method('getIncrementalOutput')->willReturnOnConsecutiveCalls(
            LspFraming::encode('{"event":"console"}').LspFraming::encode('{"event":"response"}').LspFraming::encode('{"requestId":1,"value":"outer"}'),
            LspFraming::encode('{"requestId":2,"value":"nested"}'),
            '',
        );

        $response = $this->client->sendRaw(['action' => 'page.url', 'requestId' => 1]);

        $this->assertSame(2, $calls);
        $this->assertSame('outer', $response['value']);
        $this->assertSame('nested', $nested['value'] ?? null);
    }

    public function testUnsolicitedRepliesAreNotRetained(): void
    {
        $this->mockProcess->method('getIncrementalOutput')->willReturn(
            LspFraming::encode('{"requestId":"custom_async_id","success":true}')
            .LspFraming::encode('{"id":999,"result":{}}')
            .LspFraming::encode('{"requestId":1,"value":"ok"}'),
        );

        $response = $this->client->sendRaw(['action' => 'page.url', 'requestId' => 1]);
        $responses = new \ReflectionProperty($this->client, 'responses');

        $this->assertSame('ok', $response['value']);
        $this->assertSame([], $responses->getValue($this->client));
    }
}
