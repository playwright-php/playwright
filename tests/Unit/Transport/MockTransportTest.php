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
use Playwright\Transport\MockTransport;

#[CoversClass(MockTransport::class)]
final class MockTransportTest extends TestCase
{
    public function testSendFailsWhenNotConnected(): void
    {
        $transport = new MockTransport();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Mock transport is not connected.');

        $transport->send(['action' => 'not.connected']);
    }

    public function testSendReturnsQueuedResponse(): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(['result' => 'ok']);
        $transport->connect();

        $this->assertTrue($transport->isConnected());

        $response = $transport->send(['action' => 'browserType.launch']);

        $this->assertSame(['result' => 'ok'], $response);
        $this->assertSame([['action' => 'browserType.launch']], $transport->getSentMessages());
        $this->assertSame(0, $transport->getPendingResponseCount());
    }

    public function testSendUsesMatcherAndCallback(): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(
            static fn (): array => ['result' => 'first'],
        );
        $transport->queueResponse(
            static fn (array $message): array => ['result' => $message['payload']],
            static fn (array $message): bool => 'target' === ($message['action'] ?? null),
        );
        $transport->queueResponse(
            ['result' => 'fallback'],
            static fn (): bool => true,
        );
        $transport->connect();

        $firstResponse = $transport->send([
            'action' => 'anything',
        ]);

        $secondResponse = $transport->send([
            'action' => 'target',
            'payload' => 'value',
        ]);

        $thirdResponse = $transport->send([
            'action' => 'other',
        ]);

        $this->assertSame(['result' => 'first'], $firstResponse);
        $this->assertSame(['result' => 'value'], $secondResponse);
        $this->assertSame(['result' => 'fallback'], $thirdResponse);
        $this->assertSame(0, $transport->getPendingResponseCount());
    }

    public function testSendMatcherMustReturnBoolean(): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(
            ['result' => 'never-used'],
            static fn (): string => 'yes',
        );
        $transport->connect();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('matcher must return a boolean');

        $transport->send(['action' => 'anything']);
    }

    public function testSendCallbackMustReturnArray(): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(
            static fn (): string => 'oops',
        );
        $transport->connect();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('response callback must return an array');

        $transport->send(['action' => 'bad']);
    }

    public function testSendThrowsScriptedThrowable(): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(new \RuntimeException('boom'));
        $transport->connect();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $transport->send(['action' => 'explode']);
    }

    public function testSendThrowsWhenNoResponseMatches(): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(
            ['result' => 'ignored'],
            static fn (array $message): bool => 'expected' === ($message['action'] ?? null),
        );
        $transport->connect();

        $this->expectException(\UnderflowException::class);
        $this->expectExceptionMessage('[action=other]');

        $transport->send(['action' => 'other']);
    }

    public function testSendThrowsWhenNoResponseMatchesUsesJsonEncoding(): void
    {
        $transport = new MockTransport();
        $transport->connect();

        $this->expectException(\UnderflowException::class);
        $this->expectExceptionMessage('{"foo":"bar"}');

        $transport->send(['foo' => 'bar']);
    }

    public function testSendThrowsWhenNoResponseMatchesHandlesUnserializableMessage(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $resource = fopen('php://memory', 'r');

        try {
            $this->expectException(\UnderflowException::class);
            $this->expectExceptionMessage('[unserializable-message]');

            $transport->send(['resource' => $resource]);
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    public function testSendAsyncRecordsPayloadAndInvokesHandlers(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $messages = [];
        $transport->onSendAsync(static function (array $message) use (&$messages): void {
            $messages[] = $message;
        });
        $transport->onSendAsync(static function (array $message, MockTransport $self): void {
            $self->queueResponse(['result' => $message['action'] ?? '']);
        });

        $transport->sendAsync(['action' => 'async.call']);

        $this->assertSame([['action' => 'async.call']], $transport->getAsyncMessages());
        $this->assertSame([['action' => 'async.call']], $messages);
        $this->assertSame(1, $transport->getPendingResponseCount());
    }

    public function testProcessEventsExecutesCallbacks(): void
    {
        $transport = new MockTransport();
        $transport->queueProcessEvent(static fn (MockTransport $self): int => $self->getPendingResponseCount());
        $transport->queueProcessEvent(static fn (): string => 'done');
        $transport->connect();

        $transport->processEvents();

        $this->assertSame(0, $transport->getPendingEventCount());
        $this->assertSame([0, 'done'], $transport->getProcessedEventResults());
    }

    public function testResetHistoryClearsRecordedState(): void
    {
        $transport = new MockTransport();
        $transport->queueResponse(['result' => 'ok']);
        $transport->queueProcessEvent(static fn (): string => 'x');
        $transport->connect();
        $transport->send(['action' => 'once']);
        $transport->sendAsync(['action' => 'async']);
        $transport->processEvents();

        $transport->resetHistory();
        $transport->disconnect();

        $this->assertSame([], $transport->getSentMessages());
        $this->assertSame([], $transport->getAsyncMessages());
        $this->assertSame([], $transport->getProcessedEventResults());
        $this->assertSame(0, $transport->getPendingResponseCount());
        $this->assertSame(0, $transport->getPendingEventCount());
        $this->assertFalse($transport->isConnected());
    }

    public function testStorePendingCallbackAndExecute(): void
    {
        $transport = new MockTransport();
        $transport->connect();

        $executed = false;
        $transport->storePendingCallback('req-1', static function (array $message) use (&$executed): void {
            $executed = $message['trigger'] ?? false;
        });

        $callbacks = $transport->getStoredPendingCallbacks();

        $this->assertArrayHasKey('req-1', $callbacks);

        $transport->executePendingCallback('req-1', ['trigger' => true]);

        $this->assertTrue($executed);
    }

    public function testExecutePendingCallbackThrowsForUnknownRequest(): void
    {
        $transport = new MockTransport();
        $transport->connect();

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('No pending callback stored for request "missing"');

        $transport->executePendingCallback('missing');
    }
}
