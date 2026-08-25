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

namespace Playwright\Tests\Functional\API;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\API\APIRequestContext;
use Playwright\API\APIResponse;
use Playwright\Assertions\Expect;
use Playwright\Exception\PlaywrightException;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(APIRequestContext::class)]
#[CoversClass(APIResponse::class)]
final class APIRequestContextTest extends FunctionalTestCase
{
    public function testGetReturnsStatusHeadersQueryAndJson(): void
    {
        $response = $this->context->request()->get($this->getBaseUrl().'/api/echo', [
            'headers' => ['X-Playwright-PHP' => 'api-test'],
            'params' => ['page' => '2'],
        ]);

        Expect::response($response)->toBeOK()->toHaveStatus(200);
        $this->assertStringContainsString('application/json', (string) $response->headerValue('content-type'));
        $this->assertSame('GET', $response->json()['method']);
        $this->assertSame(['page' => '2'], $response->json()['query']);
        $this->assertSame('api-test', $response->json()['requestHeader']);
    }

    public function testPostSerializesJsonData(): void
    {
        $response = $this->context->request()->post($this->getBaseUrl().'/api/echo', [
            'data' => ['name' => 'Ada', 'active' => true],
        ]);

        $this->assertSame('POST', $response->json()['method']);
        $this->assertSame(['name' => 'Ada', 'active' => true], $response->json()['json']);
    }

    public function testRequestSharesCookiesWithTheBrowserContext(): void
    {
        $this->context->addCookies([[
            'name' => 'browser-session',
            'value' => 'from-browser',
            'url' => $this->getBaseUrl(),
        ]]);

        $response = $this->context->request()->get($this->getBaseUrl().'/api/echo');

        $this->assertSame('from-browser', $response->json()['cookies']['browser-session']);
    }

    public function testResponseCookiesUpdateTheBrowserContext(): void
    {
        $this->context->request()->get($this->getBaseUrl().'/api/set-cookie');

        $cookies = $this->context->cookies([$this->getBaseUrl()]);
        $cookie = array_values(array_filter(
            $cookies,
            static fn (array $candidate): bool => 'api-session' === ($candidate['name'] ?? null)
        ));

        $this->assertCount(1, $cookie);
        $this->assertSame('from-api', $cookie[0]['value']);
    }

    public function testStorageStateContainsCookiesFromApiResponses(): void
    {
        $request = $this->context->request();
        $request->get($this->getBaseUrl().'/api/set-cookie');

        $state = $request->storageState();
        $this->assertArrayHasKey('cookies', $state);
        $this->assertArrayHasKey('origins', $state);
        $this->assertTrue((bool) array_filter(
            $state['cookies'],
            static fn (array $cookie): bool => 'api-session' === ($cookie['name'] ?? null)
        ));
    }

    public function testNonSuccessfulStatusReturnsAResponseByDefault(): void
    {
        $response = $this->context->request()->get($this->getBaseUrl().'/api/status/418');

        $this->assertFalse($response->ok());
        $this->assertSame(418, $response->status());
        $this->assertSame(['status' => 418], $response->json());
    }

    public function testFailOnStatusCodeReportsTheRequestError(): void
    {
        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('418');

        $this->context->request()->get($this->getBaseUrl().'/api/status/418', [
            'failOnStatusCode' => true,
        ]);
    }

    public function testDisposedContextCannotSendAnotherRequest(): void
    {
        $request = $this->context->request();
        $request->dispose();

        $this->expectException(PlaywrightException::class);

        $request->get($this->getBaseUrl().'/api/echo');
    }
}
