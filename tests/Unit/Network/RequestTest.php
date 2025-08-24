<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Network\Request;

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

    private function createRequest(array $data = []): Request
    {
        return new Request(array_merge($this->requestData, $data));
    }
}
