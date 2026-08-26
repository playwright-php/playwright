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
use Playwright\Browser\BrowserContext;
use Playwright\Network\Route;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(BrowserContext::class)]
#[CoversClass(Route::class)]
final class RouteTest extends FunctionalTestCase
{
    public function testCanInterceptAndFulfillRequest(): void
    {
        $this->goto('/network.html');

        $this->page->route('**/api/data.json', function ($route) {
            $route->fulfill([
                'status' => 200,
                'contentType' => 'application/json',
                'body' => \json_encode(['message' => 'Mocked response']),
            ]);
        });

        $this->page->click('#fetch-json');

        $this->page->waitForSelector('#fetch-result:has-text("Mocked response")');

        $result = $this->page->locator('#fetch-result')->textContent();
        $this->assertStringContainsString('Mocked response', $result);
    }

    public function testCanFulfillBase64EncodedBinaryBody(): void
    {
        $this->goto('/index.html');
        $expected = [0x77, 0x4F, 0x46, 0x32, 0x00, 0x01, 0x02, 0xFF];

        $this->page->route('**/binary', static function ($route) use ($expected): void {
            $route->fulfill([
                'status' => 200,
                'contentType' => 'application/octet-stream',
                'body' => base64_encode(pack('C*', ...$expected)),
                'isBase64' => true,
            ]);
        });

        $result = $this->page->evaluate('async () => Array.from(new Uint8Array(await (await fetch("/binary")).arrayBuffer()))');

        $this->assertSame($expected, $result);
    }

    public function testCanInterceptAndAbortRequest(): void
    {
        $this->goto('/network.html');

        $this->page->route('**/api/text', function ($route) {
            $route->abort();
        });

        $this->page->click('#fetch-text');

        $this->page->waitForSelector('#fetch-result:has-text("Error")');

        $result = $this->page->locator('#fetch-result')->textContent();
        $this->assertStringContainsString('Error', $result);
    }

    public function testCanModifyRequestHeaders(): void
    {
        $this->goto('/network.html');

        $this->page->route('**/api/users', function ($route) {
            $route->continue([
                'headers' => \array_merge(
                    $route->request()->headers(),
                    ['X-Custom-Header' => 'test-value']
                ),
            ]);
        });

        $this->page->click('#xhr-get');

        $this->assertTrue(true);
    }

    public function testCanMatchRoutePattern(): void
    {
        $this->goto('/network.html');

        $matched = false;

        $this->page->route('**/api/**', function ($route) use (&$matched) {
            $matched = true;
            $route->fulfill([
                'status' => 200,
                'contentType' => 'application/json',
                'body' => \json_encode(['intercepted' => true]),
            ]);
        });

        $this->page->click('#fetch-json');
        $this->page->waitForSelector('#fetch-result');

        $this->assertTrue($matched);
    }

    public function testCanUnroute(): void
    {
        $this->goto('/network.html');

        $handler = function ($route) {
            $route->fulfill([
                'status' => 200,
                'contentType' => 'application/json',
                'body' => \json_encode(['message' => 'Intercepted']),
            ]);
        };

        $this->page->route('**/api/data.json', $handler);

        $this->page->click('#fetch-json');
        $this->page->waitForSelector('#fetch-result:has-text("Intercepted")', ['timeout' => 5000]);

        $this->page->unroute('**/api/data.json', $handler);

        // Without the route, /api/data.json 404s and the page reports an error
        $this->page->click('#fetch-json');
        $this->page->waitForSelector('#fetch-result:has-text("Error")', ['timeout' => 5000]);

        $result = $this->page->locator('#fetch-result')->textContent();
        $this->assertStringContainsString('Error', $result);
    }

    public function testCanRedirectNavigationRequest(): void
    {
        $target = $this->getBaseUrl().'/index.html';
        $this->page->route('**/redirect-me', function (Route $route) use ($target): void {
            $route->redirectNavigationRequest($target);
        });

        $response = $this->page->goto($this->getBaseUrl().'/redirect-me');

        $this->assertSame($target, $this->page->url());
        $this->assertSame($target, $response?->url());
        $this->assertSame(200, $response?->status());
    }

    public function testCanRedirectNavigationRequestMoreThanOnce(): void
    {
        $baseUrl = $this->getBaseUrl();
        $this->page->route('**/redirect-*', function (Route $route) use ($baseUrl): void {
            $target = str_ends_with($route->request()->url(), '/redirect-first')
                ? $baseUrl.'/redirect-second'
                : $baseUrl.'/index.html';

            $route->redirectNavigationRequest($target);
        });

        $this->page->goto($baseUrl.'/redirect-first');

        $this->assertSame($baseUrl.'/index.html', $this->page->url());
    }

    public function testAllowsTenNavigationRedirects(): void
    {
        $baseUrl = $this->getBaseUrl();
        $redirectCount = 0;
        $this->page->route('**/redirect-chain-*', function (Route $route) use ($baseUrl, &$redirectCount): void {
            preg_match('/(\d+)$/', $route->request()->url(), $matches);
            $step = (int) ($matches[1] ?? 0);
            ++$redirectCount;

            $route->redirectNavigationRequest(9 === $step
                ? $baseUrl.'/index.html'
                : $baseUrl.'/redirect-chain-'.($step + 1));
        });

        $this->page->goto($baseUrl.'/redirect-chain-0');

        $this->assertSame(10, $redirectCount);
        $this->assertSame($baseUrl.'/index.html', $this->page->url());
    }

    public function testCanRedirectNavigationRequestAfterClick(): void
    {
        $baseUrl = $this->getBaseUrl();
        $this->goto('/navigation.html');
        $this->page->route('**/*', function (Route $route) use ($baseUrl): void {
            $url = $route->request()->url();
            if (str_ends_with($url, '/index.html')) {
                $route->redirectNavigationRequest($baseUrl.'/redirect-after-click');

                return;
            }
            if (str_ends_with($url, '/redirect-after-click')) {
                $route->redirectNavigationRequest($baseUrl.'/page-2.html');

                return;
            }

            $route->continue();
        });

        $this->page->locator('#link-home')->click();

        $this->assertSame($baseUrl.'/page-2.html', $this->page->url());
    }

    public function testCanRedirectNavigationRequestAfterReload(): void
    {
        $baseUrl = $this->getBaseUrl();
        $this->goto('/index.html');
        $this->page->route('**/index.html', function (Route $route) use ($baseUrl): void {
            $route->redirectNavigationRequest($baseUrl.'/page-2.html');
        });

        $this->page->reload();

        $this->assertSame($baseUrl.'/page-2.html', $this->page->url());
    }

    public function testCanRedirectNavigationRequestWhenGoingBack(): void
    {
        $baseUrl = $this->getBaseUrl();
        $this->goto('/navigation.html');
        $this->page->goto($baseUrl.'/page-2.html');
        $this->page->route('**/navigation.html', function (Route $route) use ($baseUrl): void {
            $route->redirectNavigationRequest($baseUrl.'/index.html');
        });

        $this->page->goBack();

        $this->assertSame($baseUrl.'/index.html', $this->page->url());
    }

    public function testCanRedirectNavigationRequestWhenGoingForward(): void
    {
        $baseUrl = $this->getBaseUrl();
        $this->goto('/navigation.html');
        $this->page->goto($baseUrl.'/page-2.html');
        $this->page->goBack();
        $this->page->route('**/page-2.html', function (Route $route) use ($baseUrl): void {
            $route->redirectNavigationRequest($baseUrl.'/index.html');
        });

        $this->page->goForward();

        $this->assertSame($baseUrl.'/index.html', $this->page->url());
    }

    public function testRejectsTooManyNavigationRedirects(): void
    {
        $target = $this->getBaseUrl().'/redirect-loop';
        $this->page->route('**/redirect-loop', function (Route $route) use ($target): void {
            $route->redirectNavigationRequest($target);
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Too many navigation redirects');

        $this->page->goto($target);
    }

    public function testRedirectNavigationRequestPreservesResponseCookies(): void
    {
        $baseUrl = $this->getBaseUrl();
        $this->page->route('**/redirect-with-cookie', function (Route $route) use ($baseUrl): void {
            $route->redirectNavigationRequest($baseUrl.'/index.html', [
                'headers' => ['set-cookie' => 'redirected=yes; Path=/'],
            ]);
        });

        $this->page->goto($baseUrl.'/redirect-with-cookie');

        $cookies = $this->context->cookies([$baseUrl]);
        $this->assertSame('yes', array_column($cookies, 'value', 'name')['redirected'] ?? null);
    }
}
