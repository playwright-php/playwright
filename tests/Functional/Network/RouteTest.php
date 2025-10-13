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
        self::assertStringContainsString('Mocked response', $result);
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
        self::assertStringContainsString('Error', $result);
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

        self::assertTrue(true);
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

        self::assertTrue($matched);
    }

    public function testCanUnroute(): void
    {
        $this->goto('/network.html');

        $handler = function ($route) {
            $route->fulfill([
                'status' => 200,
                'contentType' => 'text/plain',
                'body' => 'Intercepted',
            ]);
        };

        $this->page->route('**/api/text', $handler);
        $this->page->unroute('**/api/text', $handler);

        self::assertTrue(true);
    }
}
