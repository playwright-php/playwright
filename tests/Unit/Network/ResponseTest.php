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

namespace Playwright\Tests\Unit\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Playwright\Network\Request;
use Playwright\Network\Response;
use Playwright\Transport\TransportInterface;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    private MockObject&TransportInterface $transport;

    private array $responseData;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->responseData = [
            'url' => 'https://example.com',
            'status' => 200,
            'statusText' => 'OK',
            'headers' => ['content-type' => 'application/json'],
            'responseId' => 'response123',
        ];
    }

    public function testUrl(): void
    {
        $response = $this->createResponse();
        $this->assertSame('https://example.com', $response->url());
    }

    public function testStatus(): void
    {
        $response = $this->createResponse();
        $this->assertSame(200, $response->status());
    }

    public function testStatusText(): void
    {
        $response = $this->createResponse();
        $this->assertSame('OK', $response->statusText());
    }

    #[DataProvider('okStatusProvider')]
    public function testOk(int $status, bool $expected): void
    {
        $response = $this->createResponse(['status' => $status]);
        $this->assertSame($expected, $response->ok());
    }

    public static function okStatusProvider(): \Generator
    {
        yield 'OK 200' => [200, true];
        yield 'OK 204' => [204, true];
        yield 'OK 299' => [299, true];
        yield 'Not OK 199' => [199, false];
        yield 'Not OK 300' => [300, false];
        yield 'Not OK 404' => [404, false];
        yield 'Not OK 500' => [500, false];
    }

    public function testHeaders(): void
    {
        $response = $this->createResponse();
        $this->assertSame(['content-type' => 'application/json'], $response->headers());
    }

    public function testBodyCachesResult(): void
    {
        $response = $this->createResponse();
        $bodyContent = 'Hello, World!';
        $encodedBody = base64_encode($bodyContent);

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'response.body',
                'pageId' => 'page123',
                'responseId' => 'response123',
            ])
            ->willReturn(['binary' => $encodedBody]);

        $this->assertSame($bodyContent, $response->body());
        $this->assertSame($bodyContent, $response->body(), 'Response has not been cached');
    }

    public function testTextIsAnAliasForBody(): void
    {
        $response = $this->createResponse();
        $bodyContent = 'Some text content';
        $encodedBody = base64_encode($bodyContent);

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['binary' => $encodedBody]);

        $this->assertSame($bodyContent, $response->text());
        $this->assertSame($bodyContent, $response->text(), 'Response has not been cached');
    }

    public function testJsonDecodesAndCachesResult(): void
    {
        $response = $this->createResponse();
        $jsonArray = ['foo' => 'bar', 'baz' => 123];
        $jsonString = json_encode($jsonArray, JSON_THROW_ON_ERROR);
        $encodedBody = base64_encode($jsonString);

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['binary' => $encodedBody]);

        $this->assertSame($jsonArray, $response->json());
        $this->assertSame($jsonArray, $response->json(), 'Response has not been cached');
    }

    public function testJsonThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Invalid JSON: Syntax error');

        $response = $this->createResponse();
        $invalidJsonString = '{"foo": "bar",}';
        $encodedBody = base64_encode($invalidJsonString);

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['binary' => $encodedBody]);

        $response->json();
    }

    public function testAllHeaders(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'response.allHeaders' === $payload['action']
                    && 'page123' === $payload['pageId']
                    && 'response123' === $payload['responseId'];
            }))
            ->willReturn(['content-type' => 'application/json', 'x-custom' => 'value']);

        $result = $response->allHeaders();
        $this->assertSame(['content-type' => 'application/json', 'x-custom' => 'value'], $result);
    }

    public function testFinished(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['error' => 'Network error']);

        $this->assertSame('Network error', $response->finished());
    }

    public function testFinishedReturnsNull(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $this->assertNull($response->finished());
    }

    public function testFrame(): void
    {
        $response = $this->createResponse();
        $this->assertNull($response->frame());
    }

    public function testFromServiceWorker(): void
    {
        $response = $this->createResponse(['fromServiceWorker' => true]);
        $this->assertTrue($response->fromServiceWorker());

        $response = $this->createResponse(['fromServiceWorker' => false]);
        $this->assertFalse($response->fromServiceWorker());

        $response = $this->createResponse();
        $this->assertFalse($response->fromServiceWorker());
    }

    public function testHeaderValue(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'response.headerValue' === $payload['action']
                    && 'Content-Type' === $payload['name'];
            }))
            ->willReturn(['value' => 'application/json']);

        $this->assertSame('application/json', $response->headerValue('Content-Type'));
    }

    public function testHeaderValueReturnsNull(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $this->assertNull($response->headerValue('Missing'));
    }

    public function testHeaderValues(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'response.headerValues' === $payload['action']
                    && 'Set-Cookie' === $payload['name'];
            }))
            ->willReturn(['values' => ['cookie1=value1', 'cookie2=value2']]);

        $result = $response->headerValues('Set-Cookie');
        $this->assertSame(['cookie1=value1', 'cookie2=value2'], $result);
    }

    public function testHeaderValuesReturnsEmpty(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $this->assertSame([], $response->headerValues('Missing'));
    }

    public function testHeadersArray(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([
                ['name' => 'content-type', 'value' => 'application/json'],
                ['name' => 'x-custom', 'value' => 'value'],
            ]);

        $result = $response->headersArray();
        $this->assertCount(2, $result);
        $this->assertSame(['name' => 'content-type', 'value' => 'application/json'], $result[0]);
        $this->assertSame(['name' => 'x-custom', 'value' => 'value'], $result[1]);
    }

    public function testRequest(): void
    {
        $requestData = [
            'url' => 'https://example.com/api',
            'method' => 'GET',
            'headers' => [],
            'resourceType' => 'fetch',
            'requestId' => 'req-123',
        ];
        $response = $this->createResponse(['request' => $requestData]);
        $request = $response->request();

        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame('https://example.com/api', $request->url());
        $this->assertSame('GET', $request->method());
    }

    public function testSecurityDetails(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([
                'issuer' => 'Let\'s Encrypt',
                'protocol' => 'TLS 1.3',
                'subjectName' => 'example.com',
                'validFrom' => 1609459200,
                'validTo' => 1640995200,
            ]);

        $result = $response->securityDetails();
        $this->assertSame('Let\'s Encrypt', $result['issuer']);
        $this->assertSame('TLS 1.3', $result['protocol']);
        $this->assertSame('example.com', $result['subjectName']);
        $this->assertSame(1609459200, $result['validFrom']);
        $this->assertSame(1640995200, $result['validTo']);
    }

    public function testSecurityDetailsReturnsNull(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $this->assertNull($response->securityDetails());
    }

    public function testServerAddr(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([
                'ipAddress' => '93.184.216.34',
                'port' => 443,
            ]);

        $result = $response->serverAddr();
        $this->assertSame('93.184.216.34', $result['ipAddress']);
        $this->assertSame(443, $result['port']);
    }

    public function testServerAddrReturnsNull(): void
    {
        $response = $this->createResponse();
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $this->assertNull($response->serverAddr());
    }

    private function createResponse(array $data = []): Response
    {
        return new Response(
            $this->transport,
            'page123',
            array_merge($this->responseData, $data)
        );
    }
}
