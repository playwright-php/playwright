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

namespace Playwright\Tests\Unit\API;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\API\APIRequestContext;
use Playwright\API\APIResponseInterface;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Tracing\TracingInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(APIRequestContext::class)]
final class APIRequestContextTest extends TestCase
{
    private TransportInterface $transport;
    private APIRequestContext $context;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->context = new APIRequestContext($this->transport, 'context_1');
    }

    public function testStandaloneContextDoesNotShareCookies(): void
    {
        $this->assertFalse($this->context->getShareCookies());
    }

    public function testGetSendsARequestAndReturnsTheResponse(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'api.fetch',
                'contextId' => 'context_1',
                'url' => 'https://example.test/users',
                'options' => ['headers' => ['Accept' => 'application/json'], 'method' => 'GET'],
            ])
            ->willReturn([
                'response' => [
                    'url' => 'https://example.test/users',
                    'status' => 200,
                    'statusText' => 'OK',
                    'headers' => ['content-type' => 'application/json'],
                    'body' => base64_encode('{"users":[]}'),
                    'bodyEncoding' => 'base64',
                ],
            ]);

        $response = $this->context->get('https://example.test/users', [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $this->assertInstanceOf(APIResponseInterface::class, $response);
        $this->assertSame(200, $response->status());
        $this->assertSame(['users' => []], $response->json());
    }

    public function testRelativeUrlsResolveAgainstTheBaseUrl(): void
    {
        $context = new APIRequestContext($this->transport, 'context_1', 'https://example.test/api/');

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'api.fetch',
                'contextId' => 'context_1',
                'url' => 'https://example.test/api/users',
                'options' => ['method' => 'GET'],
            ])
            ->willReturn(['response' => ['status' => 204]]);

        $context->get('/users');
    }

    public function testAbsoluteUrlsIgnoreTheBaseUrl(): void
    {
        $context = new APIRequestContext($this->transport, 'context_1', 'https://example.test/api');

        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (array $message): bool => 'https://other.test/users' === $message['url']))
            ->willReturn(['response' => ['status' => 200]]);

        $context->get('https://other.test/users');
    }

    public function testConvenienceMethodsSetTheirHttpMethod(): void
    {
        $messages = [];
        $this->transport->expects($this->exactly(4))
            ->method('send')
            ->willReturnCallback(static function (array $message) use (&$messages): array {
                $messages[] = $message;

                return ['response' => ['status' => 200]];
            });

        $this->context->put('https://example.test/resource');
        $this->context->patch('https://example.test/resource');
        $this->context->delete('https://example.test/resource');
        $this->context->head('https://example.test/resource');

        $this->assertSame(
            ['PUT', 'PATCH', 'DELETE', 'HEAD'],
            array_column(array_column($messages, 'options'), 'method')
        );
    }

    public function testArrayDataIsSentAsJson(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'api.fetch',
                'contextId' => 'context_1',
                'url' => 'https://example.test/users',
                'options' => [
                    'data' => '{"name":"Ada"}',
                    'method' => 'POST',
                    'headers' => ['Content-Type' => 'application/json'],
                ],
            ])
            ->willReturn(['response' => ['status' => 201]]);

        $this->context->post('https://example.test/users', ['data' => ['name' => 'Ada']]);
    }

    public function testFormDataIsUrlEncoded(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'api.fetch',
                'contextId' => 'context_1',
                'url' => 'https://example.test/login',
                'options' => [
                    'method' => 'POST',
                    'data' => 'email=ada%40example.test',
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                ],
            ])
            ->willReturn(['response' => ['status' => 200]]);

        $this->context->post('https://example.test/login', ['form' => ['email' => 'ada@example.test']]);
    }

    public function testStorageStateReturnsCookiesAndOrigins(): void
    {
        $state = [
            'cookies' => [['name' => 'session', 'value' => 'abc']],
            'origins' => [],
        ];

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'api.storageState',
                'contextId' => 'context_1',
                'path' => '/tmp/state.json',
            ])
            ->willReturn(['storageState' => $state]);

        $this->assertSame($state, $this->context->storageState('/tmp/state.json'));
    }

    public function testStorageStateRejectsAnInvalidResponse(): void
    {
        $this->transport->method('send')->willReturn(['storageState' => 'invalid']);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid API storageState response');

        $this->context->storageState();
    }

    public function testFetchReportsTransportErrors(): void
    {
        $this->transport->method('send')->willReturn(['error' => ['message' => 'Connection refused']]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Connection refused');

        $this->context->get('https://example.test');
    }

    public function testFetchRejectsAnInvalidResponse(): void
    {
        $this->transport->method('send')->willReturn(['success' => true]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid API response from transport');

        $this->context->get('https://example.test');
    }

    public function testDisposeReleasesTheRequestContext(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'api.dispose',
                'contextId' => 'context_1',
            ])
            ->willReturn([]);

        $this->context->dispose();
    }

    public function testTracingReturnsATracingInstance(): void
    {
        $this->assertInstanceOf(TracingInterface::class, $this->context->tracing());
    }

    public function testTracingTargetsTheSameContext(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'tracingStartHar',
                'contextId' => 'context_1',
                'path' => '/tmp/api.har',
                'options' => [],
            ])
            ->willReturn([]);

        $this->context->tracing()->startHar('/tmp/api.har');
    }
}
