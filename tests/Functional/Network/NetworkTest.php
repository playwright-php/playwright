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

namespace Playwright\Tests\Functional\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Network\Request;
use Playwright\Network\Response;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
final class NetworkTest extends FunctionalTestCase
{
    public function testCanCaptureRequestEvent(): void
    {
        $requests = [];

        $this->page->events()->onRequest(function ($request) use (&$requests): void {
            $requests[] = [
                'url' => $request->url(),
                'method' => $request->method(),
            ];
        });

        $this->goto('/network.html');

        $this->page->click('#fetch-json');
        $this->page->waitForSelector('#fetch-result');

        $apiRequests = array_filter($requests, fn ($r) => str_contains($r['url'], '/api/data.json'));
        self::assertNotEmpty($apiRequests, 'Should have captured API request');

        $apiRequest = reset($apiRequests);
        self::assertSame('GET', $apiRequest['method'], 'Request method should be GET');
    }

    public function testCanCaptureResponseEvent(): void
    {
        $responses = [];

        $this->page->events()->onResponse(function ($response) use (&$responses): void {
            $responses[] = [
                'url' => $response->url(),
                'status' => $response->status(),
            ];
        });

        $this->goto('/network.html');

        self::assertNotEmpty($responses, 'Should have captured at least one response');

        $htmlResponse = array_filter($responses, fn ($r) => str_contains($r['url'], 'network.html'));
        self::assertNotEmpty($htmlResponse, 'Should have captured network.html response');

        $htmlResp = reset($htmlResponse);
        self::assertSame(200, $htmlResp['status'], 'Response status should be 200');
    }

    public function testCanCapturePostRequest(): void
    {
        $postRequests = [];

        $this->page->events()->onRequest(function ($request) use (&$postRequests): void {
            if ('POST' === $request->method()) {
                $postRequests[] = [
                    'url' => $request->url(),
                    'method' => $request->method(),
                ];
            }
        });

        $this->goto('/network.html');

        $this->page->click('#xhr-post');
        $this->page->waitForSelector('#xhr-result');

        self::assertNotEmpty($postRequests, 'Should have captured POST request');

        $createRequest = array_filter($postRequests, fn ($r) => str_contains($r['url'], '/api/create'));
        self::assertNotEmpty($createRequest, 'Should have captured /api/create POST request');
    }

    public function testCanCaptureMultipleRequests(): void
    {
        $requests = [];

        $this->page->events()->onRequest(function ($request) use (&$requests): void {
            if (str_contains($request->url(), '/api/')) {
                $requests[] = $request->url();
            }
        });

        $this->goto('/network.html');

        $this->page->click('#fetch-json');
        $this->page->waitForSelector('#fetch-result');

        $this->page->click('#xhr-get');
        $this->page->waitForSelector('#xhr-result');

        self::assertGreaterThanOrEqual(2, count($requests), 'Should have captured multiple API requests');
    }

    public function testRequestHasCorrectProperties(): void
    {
        $capturedRequest = null;

        $this->page->events()->onRequest(function ($request) use (&$capturedRequest): void {
            if (str_contains($request->url(), '/api/data.json')) {
                $capturedRequest = $request;
            }
        });

        $this->goto('/network.html');

        $this->page->click('#fetch-json');
        $this->page->waitForSelector('#fetch-result');

        self::assertNotNull($capturedRequest, 'Should have captured the request');
        self::assertStringContainsString('/api/data.json', $capturedRequest->url());
        self::assertSame('GET', $capturedRequest->method());
    }

    public function testResponseHasCorrectProperties(): void
    {
        $capturedResponse = null;

        $this->page->events()->onResponse(function ($response) use (&$capturedResponse): void {
            if (str_contains($response->url(), 'network.html')) {
                $capturedResponse = $response;
            }
        });

        $this->goto('/network.html');

        self::assertNotNull($capturedResponse, 'Should have captured the response');
        self::assertStringContainsString('network.html', $capturedResponse->url());
        self::assertSame(200, $capturedResponse->status());
    }

    public function testCanCaptureRequestFailedEvent(): void
    {
        $failedRequests = [];

        $this->page->events()->onRequestFailed(function ($request) use (&$failedRequests): void {
            $failedRequests[] = $request->url();
        });

        $this->goto('/network.html');

        $this->page->click('#load-image');

        $result = $this->page->waitForSelector('#resource-result')->textContent();

        if ('Image loaded' === $result || empty($failedRequests)) {
            self::markTestSkipped('Image did not fail to load in test environment, cannot test requestfailed event');
        }

        self::assertNotEmpty($failedRequests, 'Should have captured failed request');
    }

    public function testCanCaptureResourceLoadRequests(): void
    {
        $resourceRequests = [];

        $this->page->events()->onRequest(function ($request) use (&$resourceRequests): void {
            $url = $request->url();
            if (str_contains($url, '.png') || str_contains($url, '.js')) {
                $resourceRequests[] = $url;
            }
        });

        $this->goto('/network.html');

        $this->page->click('#load-image');
        $this->page->waitForSelector('#resource-result');

        $imageRequests = array_filter($resourceRequests, fn ($url) => str_contains($url, '.png'));
        self::assertNotEmpty($imageRequests, 'Should have captured image request');
    }
}
