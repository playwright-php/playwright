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
use Playwright\API\APIResponse;
use Playwright\Exception\PlaywrightException;

#[CoversClass(APIResponse::class)]
final class APIResponseTest extends TestCase
{
    public function testResponseMetadataAndBodyAreAvailable(): void
    {
        $response = new APIResponse([
            'status' => 201,
            'statusText' => 'Created',
            'url' => 'https://example.test/users/1',
            'headers' => ['content-type' => 'application/json'],
            'body' => base64_encode('{"id":1}'),
            'bodyEncoding' => 'base64',
        ]);

        $this->assertTrue($response->ok());
        $this->assertSame(201, $response->status());
        $this->assertSame('Created', $response->statusText());
        $this->assertSame('https://example.test/users/1', $response->url());
        $this->assertSame('{"id":1}', $response->body());
        $this->assertSame('{"id":1}', $response->text());
        $this->assertSame(['id' => 1], $response->json());
        $this->assertSame('application/json', $response->headerValue('Content-Type'));
    }

    public function testNonSuccessfulStatusIsNotOk(): void
    {
        $this->assertFalse((new APIResponse(['status' => 404]))->ok());
    }

    public function testHeadersArrayPreservesRepeatedValues(): void
    {
        $response = new APIResponse([
            'headers' => ['set-cookie' => 'first=1\nsecond=2'],
            'headersArray' => [
                ['name' => 'set-cookie', 'value' => 'first=1'],
                ['name' => 'set-cookie', 'value' => 'second=2'],
                ['name' => 'x-request-id', 'value' => 'request-1'],
            ],
        ]);

        $this->assertSame([
            'set-cookie' => ['first=1', 'second=2'],
            'x-request-id' => ['request-1'],
        ], $response->headersArray());
        $this->assertSame(
            ['set-cookie' => ['first=1', 'second=2']],
            $response->headerValues('Set-Cookie')
        );
    }

    public function testHeadersArrayFallsBackToNormalizedHeaders(): void
    {
        $response = new APIResponse([
            'headers' => [
                'x-one' => 'one',
                'x-many' => ['first', 'second'],
                0 => 'ignored',
            ],
        ]);

        $this->assertSame([
            'x-one' => ['one'],
            'x-many' => ['first', 'second'],
        ], $response->headersArray());
    }

    public function testInvalidJsonRaisesAPlaywrightException(): void
    {
        $response = new APIResponse(['body' => base64_encode('not json'), 'bodyEncoding' => 'base64']);

        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('Response body is not valid JSON');

        $response->json();
    }

    public function testInvalidBase64RaisesAPlaywrightException(): void
    {
        $response = new APIResponse(['body' => '*invalid*', 'bodyEncoding' => 'base64']);

        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('API response body is not valid base64');

        $response->text();
    }

    public function testMissingValuesUseSafeDefaults(): void
    {
        $response = new APIResponse([]);

        $this->assertSame(0, $response->status());
        $this->assertSame('', $response->statusText());
        $this->assertSame('', $response->url());
        $this->assertSame([], $response->headers());
        $this->assertNull($response->headerValue('missing'));
        $this->assertSame('', $response->text());

        $response->dispose();
        $this->addToAssertionCount(1);
    }
}
