<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Transport\ProcessTransport;
use Psr\Log\NullLogger;

#[CoversClass(ProcessTransport::class)]
class ProcessTransportTest extends TestCase
{
    private ProcessTransport $transport;
    private NullLogger $logger;

    public function setUp(): void
    {
        $this->logger = new NullLogger();
        $this->transport = new ProcessTransport([
            'command' => ['echo', 'READY'],
            'timeout' => 5,
            'verbose' => false,
        ], $this->logger);
    }

    #[Test]
    public function itCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ProcessTransport::class, $this->transport);
        $this->assertFalse($this->transport->isConnected());
    }

    #[Test]
    public function itCanAddEventDispatcher(): void
    {
        $dispatcher = new class {
            public function dispatchEvent(string $event, array $params): void
            {
                // Mock dispatcher
            }
        };

        $this->transport->addEventDispatcher('test-id', $dispatcher);
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function itCanProcessEvents(): void
    {
        $this->transport->processEvents();
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function itThrowsExceptionWhenSendingWithoutConnection(): void
    {
        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Transport not connected');

        $this->transport->send(['test' => 'message']);
    }

    #[Test]
    public function itThrowsExceptionWhenSendingAsyncWithoutConnection(): void
    {
        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Transport not connected');

        $this->transport->sendAsync(['test' => 'message']);
    }

    #[Test]
    public function itCanDisconnectSafely(): void
    {
        $this->transport->disconnect();
        $this->assertFalse($this->transport->isConnected());
    }

    #[Test]
    public function itHandlesTimeoutConfiguration(): void
    {
        $transport = new ProcessTransport([
            'command' => ['sleep', '10'],
            'timeout' => 1,
        ], $this->logger);

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to start Playwright server: Playwright server did not start or respond with READY');
        $transport->connect();
    }

    #[Test]
    public function itHandlesVerboseLogging(): void
    {
        $transport = new ProcessTransport([
            'command' => ['echo', 'READY'],
            'verbose' => true,
        ], $this->logger);

        $this->assertInstanceOf(ProcessTransport::class, $transport);
    }

    #[Test]
    public function itCanBeConstructedWithDefaultLogger(): void
    {
        $transport = new ProcessTransport(['command' => ['echo', 'test']]);
        $this->assertInstanceOf(ProcessTransport::class, $transport);
    }
}
