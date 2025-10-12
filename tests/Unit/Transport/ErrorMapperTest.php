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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\DisconnectedException;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Exception\TimeoutException;
use Playwright\Transport\ErrorMapper;

#[CoversClass(ErrorMapper::class)]
class ErrorMapperTest extends TestCase
{
    #[Test]
    public function itMapsTimeoutErrors(): void
    {
        $error = [
            'name' => 'TimeoutError',
            'message' => 'Timeout of 5000ms exceeded',
            'code' => 408,
            'stack' => 'Error stack trace',
        ];

        $exception = ErrorMapper::toException(
            $error,
            'Page.navigate',
            ['url' => 'https://example.com'],
            5000.0
        );

        $this->assertInstanceOf(TimeoutException::class, $exception);
        $this->assertEquals('Timeout of 5000ms exceeded', $exception->getMessage());
        $this->assertEquals(5000.0, $exception->getTimeoutMs());

        $context = $exception->getContext();
        $this->assertEquals('Page.navigate', $context['method']);
        $this->assertEquals('TimeoutError', $context['protocolName']);
        $this->assertEquals(408, $context['protocolCode']);
        $this->assertEquals('Error stack trace', $context['remoteStack']);
    }

    #[Test]
    public function itMapsTimeoutByCode(): void
    {
        $error = [
            'message' => 'Request timeout',
            'code' => 408,
        ];

        $exception = ErrorMapper::toException($error, null, null, null);

        $this->assertInstanceOf(TimeoutException::class, $exception);
        $this->assertEquals('Request timeout', $exception->getMessage());
    }

    #[Test]
    public function itMapsDisconnectionErrors(): void
    {
        $error = [
            'name' => 'TargetClosedError',
            'message' => 'Target was closed',
            'code' => 0,
        ];

        $exception = ErrorMapper::toException($error, 'Page.click', null, null);

        $this->assertInstanceOf(DisconnectedException::class, $exception);
        $this->assertEquals('Target was closed', $exception->getMessage());

        $context = $exception->getContext();
        $this->assertEquals('Page.click', $context['method']);
        $this->assertEquals('TargetClosedError', $context['protocolName']);
    }

    #[Test]
    public function itMapsDisconnectedError(): void
    {
        $error = [
            'name' => 'DisconnectedError',
            'message' => 'Connection lost',
        ];

        $exception = ErrorMapper::toException($error, null, null, null);

        $this->assertInstanceOf(DisconnectedException::class, $exception);
        $this->assertEquals('Connection lost', $exception->getMessage());
    }

    #[Test]
    public function itMapsGenericProtocolErrors(): void
    {
        $error = [
            'name' => 'CustomError',
            'message' => 'Something went wrong',
            'code' => 500,
            'stack' => 'Stack trace here',
        ];

        $exception = ErrorMapper::toException(
            $error,
            'Custom.method',
            ['param1' => 'value1'],
            null
        );

        $this->assertInstanceOf(ProtocolErrorException::class, $exception);
        $this->assertEquals('Something went wrong', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals('CustomError', $exception->getProtocolName());
        $this->assertEquals('Custom.method', $exception->getMethod());
        $this->assertEquals(['param1' => 'value1'], $exception->getParams());
        $this->assertEquals('Stack trace here', $exception->getRemoteStack());
    }

    #[Test]
    public function itHandlesMissingFields(): void
    {
        $error = [];

        $exception = ErrorMapper::toException($error, null, null, null);

        $this->assertInstanceOf(ProtocolErrorException::class, $exception);
        $this->assertEquals('Protocol error', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getProtocolName());
        $this->assertNull($exception->getMethod());
        $this->assertNull($exception->getParams());
        $this->assertNull($exception->getRemoteStack());
    }

    #[Test]
    public function itSanitizesSensitiveParams(): void
    {
        $error = [
            'name' => 'Error',
            'message' => 'Failed',
        ];

        $params = [
            'username' => 'john',
            'password' => 'secret123',
            'normal' => 'data',
        ];

        $exception = ErrorMapper::toException($error, 'auth.login', $params, null);

        $this->assertInstanceOf(ProtocolErrorException::class, $exception);

        $sanitizedParams = $exception->getParams();
        $this->assertEquals('john', $sanitizedParams['username']);
        $this->assertEquals('[REDACTED]', $sanitizedParams['password']);
        $this->assertEquals('data', $sanitizedParams['normal']);
    }

    #[Test]
    public function itHandlesNumericCodes(): void
    {
        $error = [
            'name' => 'Error',
            'message' => 'Test',
            'code' => '404',
        ];

        $exception = ErrorMapper::toException($error, null, null, null);

        $this->assertInstanceOf(ProtocolErrorException::class, $exception);
        $this->assertEquals(404, $exception->getCode());
    }

    #[Test]
    public function itPrefersStackOverRemoteStack(): void
    {
        $error = [
            'name' => 'Error',
            'message' => 'Test',
            'stack' => 'Primary stack',
            'remoteStack' => 'Secondary stack',
        ];

        $exception = ErrorMapper::toException($error, null, null, null);

        $this->assertInstanceOf(ProtocolErrorException::class, $exception);
        $this->assertEquals('Primary stack', $exception->getRemoteStack());
    }

    #[Test]
    public function itFallsBackToRemoteStack(): void
    {
        $error = [
            'name' => 'Error',
            'message' => 'Test',
            'remoteStack' => 'Secondary stack',
        ];

        $exception = ErrorMapper::toException($error, null, null, null);

        $this->assertInstanceOf(ProtocolErrorException::class, $exception);
        $this->assertEquals('Secondary stack', $exception->getRemoteStack());
    }
}
