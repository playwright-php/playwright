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

    public function testHeaderValueIsCaseInsensitiveAndReturnsFirst(): void
    {
        $response = $this->createResponse(['headers' => ['Content-Type' => 'text/html, application/json', 'x-test' => 'TRUE']]);
        $this->assertSame('text/html', $response->headerValue('content-type'));
        $this->assertSame('TRUE', $response->headerValue('X-Test'));
        $this->assertNull($response->headerValue('missing'));
    }

    public function testHeaderValuesSplitsCommaSeparatedList(): void
    {
        $response = $this->createResponse(['headers' => ['accept' => 'text/html, application/json,  text/plain ']]);
        $this->assertSame(['text/html', 'application/json', 'text/plain'], $response->headerValues('ACCEPT'));
        $this->assertSame([], $response->headerValues('missing'));
    }

    public function testHeadersArrayProducesPairs(): void
    {
        $response = $this->createResponse(['headers' => ['accept' => 'text/html, application/json', 'x-one' => '1']]);
        $pairs = $response->headersArray();
        $this->assertSame([
            ['name' => 'accept', 'value' => 'text/html'],
            ['name' => 'accept', 'value' => 'application/json'],
            ['name' => 'x-one', 'value' => '1'],
        ], $pairs);
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
