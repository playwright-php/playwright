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
use PHPUnit\Framework\TestCase;
use Playwright\Network\Request;
use Playwright\Transport\TransportInterface;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    private array $requestData;

    protected function setUp(): void
    {
        $this->requestData = [
            'url' => 'https://example.com/api/data',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json', 'X-Test' => 'true'],
            'postData' => '{"key":"value"}',
            'resourceType' => 'fetch',
        ];
    }

    public function testUrl(): void
    {
        $request = $this->createRequest();
        $this->assertSame('https://example.com/api/data', $request->url());
    }

    public function testMethod(): void
    {
        $request = $this->createRequest();
        $this->assertSame('POST', $request->method());
    }

    public function testHeaders(): void
    {
        $request = $this->createRequest();
        $this->assertSame(['Content-Type' => 'application/json', 'X-Test' => 'true'], $request->headers());
    }

    public function testPostData(): void
    {
        $request = $this->createRequest();
        $this->assertSame('{"key":"value"}', $request->postData());
    }

    public function testPostDataReturnsNullWhenExplicitlyNull(): void
    {
        $request = $this->createRequest(['postData' => null]);
        $this->assertNull($request->postData());
    }

    public function testPostDataReturnsNullWhenKeyIsMissing(): void
    {
        $data = $this->requestData;
        unset($data['postData']);
        $request = new Request($data);

        $this->assertNull($request->postData());
    }

    public function testPostDataJSON(): void
    {
        $request = $this->createRequest();
        $this->assertSame(['key' => 'value'], $request->postDataJSON());
    }

    public function testPostDataJSONReturnsNullWhenPostDataIsNull(): void
    {
        $request = $this->createRequest(['postData' => null]);
        $this->assertNull($request->postDataJSON());
    }

    public function testPostDataJSONReturnsNullForInvalidJSON(): void
    {
        $request = $this->createRequest(['postData' => 'not-a-json-string']);
        $this->assertNull($request->postDataJSON());
    }

    public function testResourceType(): void
    {
        $request = $this->createRequest();
        $this->assertSame('fetch', $request->resourceType());
    }

    public function testHeaderValueIsCaseInsensitive(): void
    {
        $request = $this->createRequest(['headers' => ['Content-Type' => 'application/json', 'x-test' => 'TRUE']]);
        $this->assertSame('application/json', $request->headerValue('content-type'));
        $this->assertSame('TRUE', $request->headerValue('X-Test'));
        $this->assertNull($request->headerValue('missing'));
    }

    public function testHeadersArraySplitsCommaSeparatedValues(): void
    {
        $request = $this->createRequest(['headers' => ['accept' => 'text/html, application/json,  text/plain ']]);
        $pairs = $request->headersArray();
        $this->assertSame([
            ['name' => 'accept', 'value' => 'text/html'],
            ['name' => 'accept', 'value' => 'application/json'],
            ['name' => 'accept', 'value' => 'text/plain'],
        ], $pairs);
    }

    public function testAllHeadersIsAliasOfHeaders(): void
    {
        $request = $this->createRequest(['headers' => ['a' => '1', 'b' => '2']]);
        $this->assertSame(['a' => '1', 'b' => '2'], $request->allHeaders());
    }

    public function testAllHeadersWithoutTransport(): void
    {
        $request = $this->createRequest();
        $this->assertSame(['Content-Type' => 'application/json', 'X-Test' => 'true'], $request->allHeaders());
    }

    public function testAllHeadersWithTransport(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'request.allHeaders' === $payload['action'] && 'req-123' === $payload['requestId'];
            }))
            ->willReturn(['Content-Type' => 'application/json', 'X-Custom' => 'value']);

        $request = new Request($this->requestData, $transport, 'req-123');
        $this->assertSame(['Content-Type' => 'application/json', 'X-Custom' => 'value'], $request->allHeaders());
    }

    public function testHeadersArrayWithoutTransport(): void
    {
        $request = $this->createRequest();
        $result = $request->headersArray();
        $this->assertCount(2, $result);
        $this->assertSame(['name' => 'Content-Type', 'value' => 'application/json'], $result[0]);
        $this->assertSame(['name' => 'X-Test', 'value' => 'true'], $result[1]);
    }

    public function testHeadersArrayWithTransport(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->willReturn([
                ['name' => 'Content-Type', 'value' => 'application/json'],
                ['name' => 'X-Custom', 'value' => 'value'],
            ]);

        $request = new Request($this->requestData, $transport, 'req-123');
        $result = $request->headersArray();
        $this->assertCount(2, $result);
        $this->assertSame(['name' => 'Content-Type', 'value' => 'application/json'], $result[0]);
    }

    public function testHeaderValueWithoutTransport(): void
    {
        $request = $this->createRequest();
        $this->assertSame('application/json', $request->headerValue('Content-Type'));
        $this->assertSame('application/json', $request->headerValue('content-type'));
        $this->assertNull($request->headerValue('Missing'));
    }

    public function testHeaderValueWithTransport(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->willReturn(['value' => 'application/json']);

        $request = new Request($this->requestData, $transport, 'req-123');
        $this->assertSame('application/json', $request->headerValue('Content-Type'));
    }

    public function testIsNavigationRequest(): void
    {
        $request = $this->createRequest(['isNavigationRequest' => true]);
        $this->assertTrue($request->isNavigationRequest());

        $request = $this->createRequest(['isNavigationRequest' => false]);
        $this->assertFalse($request->isNavigationRequest());

        $request = $this->createRequest();
        $this->assertFalse($request->isNavigationRequest());
    }

    public function testPostDataBuffer(): void
    {
        $request = $this->createRequest(['postDataBuffer' => 'binary-data']);
        $this->assertSame('binary-data', $request->postDataBuffer());

        $request = $this->createRequest();
        $this->assertNull($request->postDataBuffer());
    }

    public function testFailure(): void
    {
        $request = $this->createRequest(['failure' => ['errorText' => 'net::ERR_CONNECTION_REFUSED']]);
        $this->assertSame(['errorText' => 'net::ERR_CONNECTION_REFUSED'], $request->failure());

        $request = $this->createRequest();
        $this->assertNull($request->failure());
    }

    public function testFrame(): void
    {
        $request = $this->createRequest();
        $this->assertNull($request->frame());
    }

    public function testRedirectedFrom(): void
    {
        $redirectData = [
            'url' => 'https://example.com/old',
            'method' => 'GET',
            'headers' => [],
            'resourceType' => 'document',
            'requestId' => 'req-old',
        ];
        $request = $this->createRequest(['redirectedFrom' => $redirectData]);
        $redirectedFrom = $request->redirectedFrom();

        $this->assertInstanceOf(Request::class, $redirectedFrom);
        $this->assertSame('https://example.com/old', $redirectedFrom->url());
    }

    public function testRedirectedFromReturnsNull(): void
    {
        $request = $this->createRequest();
        $this->assertNull($request->redirectedFrom());
    }

    public function testRedirectedTo(): void
    {
        $redirectData = [
            'url' => 'https://example.com/new',
            'method' => 'GET',
            'headers' => [],
            'resourceType' => 'document',
            'requestId' => 'req-new',
        ];
        $request = $this->createRequest(['redirectedTo' => $redirectData]);
        $redirectedTo = $request->redirectedTo();

        $this->assertInstanceOf(Request::class, $redirectedTo);
        $this->assertSame('https://example.com/new', $redirectedTo->url());
    }

    public function testResponse(): void
    {
        $request = $this->createRequest();
        $this->assertNull($request->response());
    }

    public function testServiceWorker(): void
    {
        $request = $this->createRequest(['serviceWorker' => 'sw-id']);
        $this->assertSame('sw-id', $request->serviceWorker());

        $request = $this->createRequest();
        $this->assertNull($request->serviceWorker());
    }

    public function testSizesWithoutTransport(): void
    {
        $request = $this->createRequest();
        $sizes = $request->sizes();
        $this->assertSame(0, $sizes['requestBodySize']);
        $this->assertSame(0, $sizes['requestHeadersSize']);
        $this->assertSame(0, $sizes['responseBodySize']);
        $this->assertSame(0, $sizes['responseHeadersSize']);
    }

    public function testSizesWithTransport(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->willReturn([
                'requestBodySize' => 100,
                'requestHeadersSize' => 200,
                'responseBodySize' => 300,
                'responseHeadersSize' => 400,
            ]);

        $request = new Request($this->requestData, $transport, 'req-123');
        $sizes = $request->sizes();
        $this->assertSame(100, $sizes['requestBodySize']);
        $this->assertSame(200, $sizes['requestHeadersSize']);
        $this->assertSame(300, $sizes['responseBodySize']);
        $this->assertSame(400, $sizes['responseHeadersSize']);
    }

    public function testTiming(): void
    {
        $timingData = [
            'startTime' => 1.0,
            'domainLookupStart' => 2.0,
            'domainLookupEnd' => 3.0,
            'connectStart' => 4.0,
            'secureConnectionStart' => 5.0,
            'connectEnd' => 6.0,
            'requestStart' => 7.0,
            'responseStart' => 8.0,
            'responseEnd' => 9.0,
        ];
        $request = $this->createRequest(['timing' => $timingData]);
        $timing = $request->timing();

        $this->assertSame(1.0, $timing['startTime']);
        $this->assertSame(2.0, $timing['domainLookupStart']);
        $this->assertSame(9.0, $timing['responseEnd']);
    }

    public function testTimingReturnsDefaultValues(): void
    {
        $request = $this->createRequest();
        $timing = $request->timing();

        $this->assertSame(-1.0, $timing['startTime']);
        $this->assertSame(-1.0, $timing['responseEnd']);
    }

    private function createRequest(array $data = []): Request
    {
        return new Request(array_merge($this->requestData, $data));
    }
}
