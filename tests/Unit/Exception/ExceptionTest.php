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

namespace Playwright\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\DisconnectedException;
use Playwright\Exception\NetworkException;
use Playwright\Exception\PlaywrightException;
use Playwright\Exception\PlaywrightExceptionInterface;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Exception\TimeoutException;

#[CoversClass(PlaywrightException::class)]
#[CoversClass(NetworkException::class)]
#[CoversClass(TimeoutException::class)]
#[CoversClass(DisconnectedException::class)]
#[CoversClass(ProtocolErrorException::class)]
class ExceptionTest extends TestCase
{
    #[Test]
    public function playwrightExceptionImplementsInterface(): void
    {
        $exception = new PlaywrightException('Test message');

        $this->assertInstanceOf(PlaywrightExceptionInterface::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    #[Test]
    public function networkExceptionExtendsPlaywrightException(): void
    {
        $exception = new NetworkException('Network error');

        $this->assertInstanceOf(PlaywrightException::class, $exception);
        $this->assertInstanceOf(PlaywrightExceptionInterface::class, $exception);
        $this->assertEquals('Network error', $exception->getMessage());
    }

    #[Test]
    public function timeoutExceptionExtendsPlaywrightException(): void
    {
        $exception = new TimeoutException('Timeout error');

        $this->assertInstanceOf(PlaywrightException::class, $exception);
        $this->assertInstanceOf(PlaywrightExceptionInterface::class, $exception);
        $this->assertEquals('Timeout error', $exception->getMessage());
    }

    #[Test]
    public function exceptionsCanBeInstantiatedWithCode(): void
    {
        $exception = new PlaywrightException('Test message', 404);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }

    #[Test]
    public function exceptionsCanBeInstantiatedWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new NetworkException('Network error', 500, $previous);

        $this->assertEquals('Network error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function exceptionsCanBeThrown(): void
    {
        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('Test exception');

        throw new PlaywrightException('Test exception');
    }

    #[Test]
    public function networkExceptionCanBeThrown(): void
    {
        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Network failure');

        throw new NetworkException('Network failure');
    }

    #[Test]
    public function timeoutExceptionCanBeThrown(): void
    {
        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Operation timed out');

        throw new TimeoutException('Operation timed out');
    }

    #[Test]
    public function playwrightExceptionSupportsContext(): void
    {
        $context = ['method' => 'click', 'selector' => '#button'];
        $exception = new PlaywrightException('Test with context', 0, null, $context);

        $this->assertEquals($context, $exception->getContext());
    }

    #[Test]
    public function timeoutExceptionIncludesTimeoutInContext(): void
    {
        $exception = new TimeoutException('Timeout occurred', 5000.0);

        $context = $exception->getContext();
        $this->assertArrayHasKey('timeoutMs', $context);
        $this->assertEquals(5000.0, $context['timeoutMs']);
        $this->assertEquals(5000.0, $exception->getTimeoutMs());
    }

    #[Test]
    public function timeoutExceptionSupportsAdditionalContext(): void
    {
        $additionalContext = ['selector' => '.button', 'action' => 'click'];
        $exception = new TimeoutException('Timeout with context', 3000.0, null, $additionalContext);

        $context = $exception->getContext();
        $this->assertEquals(3000.0, $context['timeoutMs']);
        $this->assertEquals('.button', $context['selector']);
        $this->assertEquals('click', $context['action']);
    }

    #[Test]
    public function disconnectedExceptionCanBeCreated(): void
    {
        $exception = new DisconnectedException('Connection lost');

        $this->assertInstanceOf(PlaywrightException::class, $exception);
        $this->assertEquals('Connection lost', $exception->getMessage());
    }

    #[Test]
    public function protocolErrorExceptionIncludesProtocolDetails(): void
    {
        $exception = new ProtocolErrorException(
            'Protocol error',
            500,
            'TimeoutError',
            'Page.navigate',
            ['url' => 'https://example.com'],
            'stack trace here'
        );

        $this->assertInstanceOf(PlaywrightException::class, $exception);
        $this->assertEquals('Protocol error', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals('TimeoutError', $exception->getProtocolName());
        $this->assertEquals('Page.navigate', $exception->getMethod());
        $this->assertEquals(['url' => 'https://example.com'], $exception->getParams());
        $this->assertEquals('stack trace here', $exception->getRemoteStack());

        $context = $exception->getContext();
        $this->assertArrayHasKey('protocolName', $context);
        $this->assertArrayHasKey('method', $context);
        $this->assertArrayHasKey('params', $context);
        $this->assertArrayHasKey('remoteStack', $context);
    }
}
