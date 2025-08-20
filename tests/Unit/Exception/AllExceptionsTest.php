<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Exception\DisconnectedException;
use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Exception\ProcessCrashedException;
use PlaywrightPHP\Exception\ProcessLaunchException;
use PlaywrightPHP\Exception\ProtocolErrorException;
use PlaywrightPHP\Exception\TransportException;
use PlaywrightPHP\Node\Exception\NodeBinaryNotFoundException;
use PlaywrightPHP\Node\Exception\NodeVersionTooLowException;

#[CoversClass(NetworkException::class)]
#[CoversClass(TransportException::class)]
#[CoversClass(DisconnectedException::class)]
#[CoversClass(ProcessLaunchException::class)]
#[CoversClass(NodeBinaryNotFoundException::class)]
#[CoversClass(NodeVersionTooLowException::class)]
final class AllExceptionsTest extends TestCase
{
    public function testNetworkException(): void
    {
        $exception = new NetworkException('Connection failed');

        $this->assertEquals('Connection failed', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testNetworkExceptionWithCodeAndPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new NetworkException('Network error', 500, $previous);

        $this->assertEquals('Network error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testTransportException(): void
    {
        $exception = new TransportException('Transport failed');

        $this->assertEquals('Transport failed', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testDisconnectedException(): void
    {
        $exception = new DisconnectedException('Connection lost');

        $this->assertEquals('Connection lost', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testProcessLaunchException(): void
    {
        $exception = new ProcessLaunchException('Failed to start process');

        $this->assertEquals('Failed to start process', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testProcessCrashedExceptionWithContext(): void
    {
        $exception = new ProcessCrashedException('Process crashed', 1, 'Permission denied');

        $this->assertEquals('Process crashed', $exception->getMessage());
        $this->assertEquals(1, $exception->getExitCode());
        $this->assertEquals('Permission denied', $exception->getStderrExcerpt());
    }

    public function testProtocolErrorExceptionWithDetails(): void
    {
        $exception = new ProtocolErrorException('Protocol error', 0, null, 'page.goto', ['url' => 'https://example.com']);

        $this->assertEquals('Protocol error', $exception->getMessage());
        $this->assertEquals('page.goto', $exception->getMethod());
        $this->assertEquals(['url' => 'https://example.com'], $exception->getParams());
    }

    public function testNodeBinaryNotFoundException(): void
    {
        $exception = new NodeBinaryNotFoundException('Node.js binary not found');

        $this->assertEquals('Node.js binary not found', $exception->getMessage());
    }

    public function testNodeVersionTooLowException(): void
    {
        $exception = new NodeVersionTooLowException('Node.js version too low');

        $this->assertEquals('Node.js version too low', $exception->getMessage());
    }

    public function testExceptionInheritance(): void
    {
        $this->assertInstanceOf(\RuntimeException::class, new NetworkException('test'));
        $this->assertInstanceOf(\RuntimeException::class, new TransportException('test'));
        $this->assertInstanceOf(\RuntimeException::class, new DisconnectedException('test'));
        $this->assertInstanceOf(\RuntimeException::class, new ProcessLaunchException('test'));
        $this->assertInstanceOf(\RuntimeException::class, new ProcessCrashedException('test', 0, ''));
        $this->assertInstanceOf(\RuntimeException::class, new ProtocolErrorException('test', 0));
        $this->assertInstanceOf(\RuntimeException::class, new NodeBinaryNotFoundException('test'));
        $this->assertInstanceOf(\RuntimeException::class, new NodeVersionTooLowException('test'));
    }
}
