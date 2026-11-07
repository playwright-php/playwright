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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Event\EventDispatcherInterface;
use Playwright\Exception\DisconnectedException;
use Playwright\Exception\MissingDependencyException;
use Playwright\Exception\NetworkException;
use Playwright\Exception\TimeoutException;
use Playwright\Tests\Mocks\TestLogger;
use Playwright\Transport\JsonRpc\JsonRpcClient;
use Playwright\Transport\JsonRpc\JsonRpcTransport;
use Playwright\Transport\JsonRpc\ProcessLauncherInterface;
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
            config: ['command' => ['node', 'server.js']],
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
            config: ['command' => ['node', 'server.js']],
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
            config: ['command' => ['node', 'server.js']],
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

        $transport->sendAsync(['action' => 'test']);

        $this->assertTrue($this->logger->hasWarningRecords());
    }

    public function testSendDiagnosesAMissingPackageWhenNodeAlreadyExited(): void
    {
        $process = $this->createMock(Process::class);
        $process->method('isRunning')->willReturn(false);
        $process->method('getExitCode')->willReturn(1);
        $this->processLauncher->method('start')->willReturn($process);
        $this->processLauncher->method('getInputStream')->willReturn(new InputStream());
        $this->processLauncher->method('getStderrOutput')->willReturn("Error: Cannot find module 'playwright'");

        $transport = new JsonRpcTransport($this->processLauncher, ['command' => ['node', 'server.js']], $this->logger);
        $transport->connect();

        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage('vendor/bin/playwright-install --browsers');

        $transport->send(['action' => 'launch']);
    }

    public function testProcessEventsIsNoOpButDoesNotThrow(): void
    {
        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            logger: $this->logger
        );

        $transport->processEvents();

        $this->assertTrue(true);
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
            config: ['command' => ['node', 'server.js']],
            logger: $this->logger
        );

        $transport->connect();

        $this->assertTrue($transport->isConnected());

        $transport->__destruct();

        $this->assertFalse($transport->isConnected());
    }

    public function testEventsAreDispatchedToTheRegisteredDispatcher(): void
    {
        $transport = new JsonRpcTransport(processLauncher: $this->processLauncher, logger: $this->logger);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatchEvent')->with('console', ['text' => 'hi']);
        $transport->addEventDispatcher('page_1', $dispatcher);

        $this->handleEvent($transport, ['objectId' => 'page_1', 'event' => 'console', 'params' => ['text' => 'hi']]);
    }

    public function testRemovedDispatcherNoLongerReceivesEvents(): void
    {
        $transport = new JsonRpcTransport(processLauncher: $this->processLauncher, logger: $this->logger);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatchEvent');
        $transport->addEventDispatcher('page_1', $dispatcher);
        $transport->removeEventDispatcher('page_1');

        $this->handleEvent($transport, ['objectId' => 'page_1', 'event' => 'console', 'params' => []]);

        $this->assertFalse($this->logger->hasWarningRecords());
    }

    /**
     * @param array<string, mixed> $event
     */
    private function handleEvent(JsonRpcTransport $transport, array $event): void
    {
        (new \ReflectionMethod($transport, 'handleEvent'))->invoke($transport, $event);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createConnectedTransport(array $config): JsonRpcTransport
    {
        $mockProcess = $this->createMock(Process::class);
        $mockProcess->method('isRunning')->willReturn(true);

        $this->processLauncher->method('start')->willReturn($mockProcess);
        $this->processLauncher->method('getInputStream')->willReturn($this->createMock(InputStream::class));

        $transport = new JsonRpcTransport(
            processLauncher: $this->processLauncher,
            config: $config,
            logger: $this->logger
        );
        $transport->connect();

        return $transport;
    }

    private function injectClient(JsonRpcTransport $transport, JsonRpcClient $client): void
    {
        (new \ReflectionProperty($transport, 'client'))->setValue($transport, $client);
    }

    public function testCallbackSetupFailurePreservesTheErrorAndReleasesTheCallback(): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js']]);
        $transport->storePendingCallback('popup', fn () => $this->fail('The action must not run after setup fails.'));
        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->once())->method('sendRaw')->willReturn([
            'error' => ['name' => 'TargetClosedError', 'message' => 'The popup owner has closed'],
        ]);
        $this->injectClient($transport, $client);

        try {
            $transport->send(['action' => 'page.waitForPopup', 'requestId' => 'popup']);
            $this->fail('Expected the setup error.');
        } catch (DisconnectedException $e) {
            $this->assertSame('The popup owner has closed', $e->getMessage());
            $this->assertSame('page.waitForPopup', $e->getContext()['method']);
        }

        $this->assertSame([], (new \ReflectionProperty($transport, 'pendingCallbacks'))->getValue($transport));
    }

    public function testCallbackContinuationPreservesTheTimeoutAndReleasesTheCallback(): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js']]);
        $executed = false;
        $transport->storePendingCallback('popup', static function () use (&$executed): void { $executed = true; });
        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->exactly(2))->method('sendRaw')->willReturnOnConsecutiveCalls(
            ['type' => 'callback', 'callbackType' => 'readyForAction', 'requestId' => 'popup'],
            ['error' => 'Timeout 100ms exceeded while waiting for popup', 'errorName' => 'TimeoutError'],
        );
        $this->injectClient($transport, $client);

        try {
            $transport->send(['action' => 'context.waitForPopup', 'requestId' => 'popup']);
            $this->fail('Expected the native timeout.');
        } catch (TimeoutException $e) {
            $this->assertSame('Timeout 100ms exceeded while waiting for popup', $e->getMessage());
        }

        $this->assertTrue($executed);
        $this->assertSame([], (new \ReflectionProperty($transport, 'pendingCallbacks'))->getValue($transport));
    }

    public function testThrowingCallbackIsReleasedAndItsExceptionIsPreserved(): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js']]);
        $failure = new \RuntimeException('Action failed');
        $transport->storePendingCallback('popup', static fn () => throw $failure);
        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->once())->method('sendRaw')->willReturn([
            'type' => 'callback', 'callbackType' => 'readyForAction', 'requestId' => 'popup',
        ]);
        $this->injectClient($transport, $client);

        try {
            $transport->send(['action' => 'page.waitForPopup', 'requestId' => 'popup']);
            $this->fail('Expected the action failure.');
        } catch (\RuntimeException $e) {
            $this->assertSame($failure, $e);
        }

        $this->assertSame([], (new \ReflectionProperty($transport, 'pendingCallbacks'))->getValue($transport));
    }

    /**
     * @return iterable<string, array{array{timeout?: int|null}, float}>
     */
    public static function transportTimeouts(): iterable
    {
        yield 'omitted' => [[], 30000.0];
        yield 'null' => [['timeout' => null], 30000.0];
        yield 'unlimited' => [['timeout' => 0], 0.0];
    }

    /**
     * @param array{timeout?: int|null} $config
     */
    #[DataProvider('transportTimeouts')]
    public function testDefaultTransportTimeoutIsPreservedUnlessExplicitlyDisabled(array $config, float $expectedTimeout): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js']] + $config);
        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->once())
            ->method('sendRaw')
            ->with($this->anything(), $expectedTimeout)
            ->willReturn([]);
        $this->injectClient($transport, $client);

        $transport->send(['action' => 'page.evaluate', 'expression' => 'new Promise(() => {})']);
    }

    public function testSendWithoutANativeOperationTimeoutUsesTheConfiguredDeadline(): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js'], 'timeout' => 45]);

        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->once())
            ->method('sendRaw')
            ->with($this->anything(), 45000.0)
            ->willReturn([]);
        $this->injectClient($transport, $client);

        $transport->send(['action' => 'page.evaluate', 'expression' => 'new Promise(() => {})']);
    }

    public function testSendWithNonTimeoutOptionsUsesTheConfiguredDeadline(): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js'], 'timeout' => 45]);

        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->once())
            ->method('sendRaw')
            ->with($this->anything(), 45000.0)
            ->willReturn([]);
        $this->injectClient($transport, $client);

        $transport->send([
            'action' => 'page.addScriptTag',
            'options' => ['url' => 'https://example.com/script.js'],
        ]);
    }

    public function testSendLetsANativeDefaultTimeoutOwnTheDeadline(): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js'], 'timeout' => 30]);

        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->once())
            ->method('sendRaw')
            ->with($this->anything(), 0.0)
            ->willReturn([]);
        $this->injectClient($transport, $client);

        $transport->send([
            'action' => 'locator.click',
            'options' => [],
        ]);
    }

    public function testSendDoesNotTreatConfigurationCommandsAsTimedOperations(): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js'], 'timeout' => 30]);

        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->once())
            ->method('sendRaw')
            ->with($this->anything(), 30000.0)
            ->willReturn([]);
        $this->injectClient($transport, $client);

        $transport->send([
            'action' => 'page.setDefaultTimeout',
            'timeout' => 60000,
        ]);
    }

    public function testSendDisablesTheTransportDeadlineForAnUnlimitedOperation(): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js'], 'timeout' => 30]);

        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->once())
            ->method('sendRaw')
            ->with($this->anything(), 0.0)
            ->willReturn([]);
        $this->injectClient($transport, $client);

        $transport->send([
            'action' => 'locator.click',
            'pageId' => 'page_1',
            'options' => ['timeout' => 0],
        ]);
    }

    public function testSendExtendsTheDeadlineForAnOperationTimeout(): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js']]);

        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->once())
            ->method('sendRaw')
            ->with($this->anything(), 40000.0)
            ->willReturn([]);
        $this->injectClient($transport, $client);

        $transport->send([
            'action' => 'page.waitForSelector',
            'pageId' => 'page_1',
            'selector' => '#x',
            'options' => ['timeout' => 35000],
        ]);
    }

    public function testSendKeepsTheConfiguredTimeoutWhenLarger(): void
    {
        $transport = $this->createConnectedTransport(['command' => ['node', 'server.js'], 'timeout' => 90]);

        $client = $this->createMock(JsonRpcClient::class);
        $client->expects($this->once())
            ->method('sendRaw')
            ->with($this->anything(), 90000.0)
            ->willReturn([]);
        $this->injectClient($transport, $client);

        $transport->send([
            'action' => 'page.waitForSelector',
            'pageId' => 'page_1',
            'selector' => '#x',
            'options' => ['timeout' => 5000],
        ]);
    }
}
