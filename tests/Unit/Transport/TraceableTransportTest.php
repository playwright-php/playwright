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
use Playwright\Transport\TraceableTransport;
use Playwright\Transport\TransportInterface;

#[CoversClass(TraceableTransport::class)]
final class TraceableTransportTest extends TestCase
{
    public function testConnectAndDisconnectAreRecorded(): void
    {
        $inner = new InstrumentedTransport();
        $transport = new TraceableTransport($inner);

        $transport->connect();
        $transport->disconnect();

        $this->assertFalse($inner->isConnected());
        $this->assertSame([
            ['method' => 'connect', 'exception' => null],
            ['method' => 'disconnect', 'exception' => null],
        ], $transport->getConnectionCalls());
    }

    public function testConnectionExceptionIsRecordedAndRethrown(): void
    {
        $inner = new InstrumentedTransport();
        $inner->connectException = new \RuntimeException('connect-failed');
        $transport = new TraceableTransport($inner);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('connect-failed');

        try {
            $transport->connect();
        } finally {
            $log = $transport->getConnectionCalls();
            $this->assertCount(1, $log);
            $this->assertSame('connect', $log[0]['method']);
            $this->assertInstanceOf(\RuntimeException::class, $log[0]['exception']);
            $this->assertSame('connect-failed', $log[0]['exception']->getMessage());
        }
    }

    public function testDisconnectExceptionIsRecordedAndRethrown(): void
    {
        $inner = new InstrumentedTransport();
        $inner->disconnectException = new \RuntimeException('disconnect-failed');
        $transport = new TraceableTransport($inner);

        $transport->connect();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('disconnect-failed');

        try {
            $transport->disconnect();
        } finally {
            $log = $transport->getConnectionCalls();
            $this->assertCount(2, $log);
            $this->assertSame('connect', $log[0]['method']);
            $this->assertNull($log[0]['exception']);
            $this->assertSame('disconnect', $log[1]['method']);
            $this->assertInstanceOf(\RuntimeException::class, $log[1]['exception']);
        }
    }

    public function testSendRecordsSuccessfulResponse(): void
    {
        $inner = new InstrumentedTransport();
        $inner->onSend = static function (array $message): array {
            return ['echo' => $message['action'] ?? null];
        };
        $transport = new TraceableTransport($inner);

        $result = $transport->send(['action' => 'do.it']);

        $this->assertSame(['echo' => 'do.it'], $result);
        $record = $transport->getSendCalls();
        $this->assertCount(1, $record);
        $this->assertSame(['action' => 'do.it'], $record[0]['message']);
        $this->assertSame(['echo' => 'do.it'], $record[0]['response']);
        $this->assertNull($record[0]['exception']);
    }

    public function testSendRecordsException(): void
    {
        $inner = new InstrumentedTransport();
        $inner->onSend = static function (): array {
            throw new \RuntimeException('send-failed');
        };
        $transport = new TraceableTransport($inner);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('send-failed');

        try {
            $transport->send(['action' => 'boom']);
        } finally {
            $record = $transport->getSendCalls();
            $this->assertCount(1, $record);
            $this->assertSame(['action' => 'boom'], $record[0]['message']);
            $this->assertNull($record[0]['response']);
            $this->assertInstanceOf(\RuntimeException::class, $record[0]['exception']);
            $this->assertSame('send-failed', $record[0]['exception']->getMessage());
        }
    }

    public function testSendAsyncRecordsCall(): void
    {
        $inner = new InstrumentedTransport();
        $transport = new TraceableTransport($inner);

        $transport->sendAsync(['action' => 'notify']);

        $record = $transport->getAsyncCalls();
        $this->assertCount(1, $record);
        $this->assertSame(['action' => 'notify'], $record[0]['message']);
        $this->assertNull($record[0]['exception']);
    }

    public function testSendAsyncRecordsException(): void
    {
        $inner = new InstrumentedTransport();
        $inner->onSendAsync = static function (): void {
            throw new \RuntimeException('async-failed');
        };
        $transport = new TraceableTransport($inner);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('async-failed');

        try {
            $transport->sendAsync(['action' => 'notify']);
        } finally {
            $record = $transport->getAsyncCalls();
            $this->assertCount(1, $record);
            $this->assertSame(['action' => 'notify'], $record[0]['message']);
            $this->assertInstanceOf(\RuntimeException::class, $record[0]['exception']);
        }
    }

    public function testProcessEventsRecordsCall(): void
    {
        $inner = new InstrumentedTransport();
        $transport = new TraceableTransport($inner);

        $transport->processEvents();

        $record = $transport->getProcessEventsCalls();
        $this->assertCount(1, $record);
        $this->assertNull($record[0]['exception']);
    }

    public function testProcessEventsRecordsException(): void
    {
        $inner = new InstrumentedTransport();
        $inner->onProcessEvents = static function (): void {
            throw new \RuntimeException('event-failed');
        };
        $transport = new TraceableTransport($inner);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('event-failed');

        try {
            $transport->processEvents();
        } finally {
            $record = $transport->getProcessEventsCalls();
            $this->assertCount(1, $record);
            $this->assertInstanceOf(\RuntimeException::class, $record[0]['exception']);
        }
    }

    public function testResetClearsRecordedHistory(): void
    {
        $inner = new InstrumentedTransport();
        $transport = new TraceableTransport($inner);

        $transport->connect();
        $transport->send(['action' => 'ping']);
        $transport->sendAsync(['action' => 'notify']);
        $transport->processEvents();
        $transport->disconnect();

        $transport->reset();

        $this->assertSame([], $transport->getConnectionCalls());
        $this->assertSame([], $transport->getSendCalls());
        $this->assertSame([], $transport->getAsyncCalls());
        $this->assertSame([], $transport->getProcessEventsCalls());
    }

    public function testIsConnectedDelegatesToDecoratedTransport(): void
    {
        $inner = new InstrumentedTransport();
        $transport = new TraceableTransport($inner);

        $this->assertFalse($transport->isConnected());
        $transport->connect();
        $this->assertTrue($transport->isConnected());
    }
}

/**
 * @internal
 */
final class InstrumentedTransport implements TransportInterface
{
    public ?\Throwable $connectException = null;
    public ?\Throwable $disconnectException = null;
    /** @var callable(array<string, mixed>): array|null */
    public $onSend;
    /** @var callable(array<string, mixed>): void|null */
    public $onSendAsync;
    /** @var callable(): void|null */
    public $onProcessEvents;

    private bool $connected = false;

    public function connect(): void
    {
        if ($this->connectException instanceof \Throwable) {
            throw $this->connectException;
        }

        $this->connected = true;
    }

    public function disconnect(): void
    {
        if ($this->disconnectException instanceof \Throwable) {
            throw $this->disconnectException;
        }

        $this->connected = false;
    }

    /**
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function send(array $message): array
    {
        if (is_callable($this->onSend)) {
            return ($this->onSend)($message);
        }

        return ['default' => true];
    }

    /**
     * @param array<string, mixed> $message
     */
    public function sendAsync(array $message): void
    {
        if (is_callable($this->onSendAsync)) {
            ($this->onSendAsync)($message);
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function processEvents(): void
    {
        if (is_callable($this->onProcessEvents)) {
            ($this->onProcessEvents)();
        }
    }
}
