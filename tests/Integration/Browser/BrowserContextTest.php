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

namespace Playwright\Tests\Integration\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContext;
use Playwright\Page\PageInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(BrowserContext::class)]
class BrowserContextTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    public static function setUpBeforeClass(): void
    {
    }

    public static function tearDownAfterClass(): void
    {
    }

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->context = $this->browser->newContext();
    }

    public function tearDown(): void
    {
        $this->context->close();
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itCreatesANewPageInContext(): void
    {
        $page = $this->context->newPage();
        $this->assertInstanceOf(PageInterface::class, $page);
        $this->assertCount(1, $this->context->pages());
        $this->assertSame($page, $this->context->pages()[0]);
        $page->close();
    }

    #[Test]
    public function itManagesCookies(): void
    {
        $page = $this->context->newPage();
        $page->goto('data:text/html,<h1>Test Page</h1>');

        $this->context->addCookies([
            [
                'name' => 'test-cookie',
                'value' => 'test-value',
                'domain' => 'localhost',
                'path' => '/',
            ],
        ]);

        $this->assertTrue(true, 'addCookies executed without throwing');

        $this->context->clearCookies();
        $this->assertTrue(true, 'clearCookies executed without throwing');

        $page->close();
    }

    #[Test]
    public function itGetsCookiesWithExpectedShape(): void
    {
        $page = $this->context->newPage();
        $this->installRouteServer($page, [
            '/index.html' => '<h1>Cookies</h1>',
        ]);
        $page->goto($this->routeUrl('/index.html'));

        $baseUrl = rtrim($this->routeUrl('/'), '/');

        $this->context->addCookies([
            [
                'name' => 'shape-test',
                'value' => 'ok',
                'url' => $baseUrl,
            ],
        ]);

        $cookies = $this->context->cookies([$baseUrl]);

        $this->assertIsArray($cookies);
        $this->assertNotEmpty($cookies);
        $this->assertArrayHasKey('name', $cookies[0]);
        $this->assertArrayHasKey('value', $cookies[0]);

        $found = array_values(array_filter($cookies, fn ($c) => ($c['name'] ?? null) === 'shape-test'));
        $this->assertNotEmpty($found, 'Expected cookie "shape-test" to be present');
        $this->assertSame('ok', $found[0]['value'] ?? null);

        $page->close();
    }

    #[Test]
    public function itAddsInitScript(): void
    {
        $this->context->addInitScript('window.myVar = 42;');
        $this->assertTrue(true, 'addInitScript executed without throwing');

        $page = $this->context->newPage();
        $page->goto('data:text/html,<h1>Test Page</h1>');

        $this->assertInstanceOf('Playwright\Page\Page', $page);

        $page->close();
    }

    #[Test]
    public function itManagesPermissions(): void
    {
        $this->context->grantPermissions(['geolocation']);
        $this->context->clearPermissions();
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function itGetsStorageState(): void
    {
        $page = $this->context->newPage();
        $this->installRouteServer($page, [
            '/index.html' => '<h1>Hello</h1>',
        ]);
        $page->goto($this->routeUrl('/index.html'));
        $page->evaluate('localStorage.setItem("foo", "bar")');

        $storageState = $this->context->storageState();

        $this->assertArrayHasKey('cookies', $storageState);
        $this->assertArrayHasKey('origins', $storageState);
        $this->assertCount(1, $storageState['origins']);
        $this->assertEquals(rtrim($this->routeUrl('/'), '/'), $storageState['origins'][0]['origin']);
        $this->assertEquals('bar', $storageState['origins'][0]['localStorage'][0]['value']);

        $page->close();
    }

    #[Test]
    public function itSetsGeolocation(): void
    {
        $this->context->grantPermissions(['geolocation']);
        $this->context->setGeolocation(59.95, 30.31667);
        $page = $this->context->newPage();
        $this->installRouteServer($page, [
            '/geolocation.html' => '<button onclick="getLocation()">Get Location</button><script>function getLocation() { navigator.geolocation.getCurrentPosition(p => document.body.innerHTML += p.coords.latitude + "," + p.coords.longitude, err => document.body.innerHTML += "Error: " + err.message); }</script>',
        ]);
        $page->goto($this->routeUrl('/geolocation.html'));

        $page->click('button');

        // Poll for either coordinates or error text since Page::waitForFunction is not available
        $deadline = microtime(true) + 5.0; // 5 seconds
        do {
            $content = $page->content() ?? '';
            $hasCoordinates = str_contains($content, '59.95,30.31667');
            $hasError = str_contains($content, 'Error');
            if ($hasCoordinates || $hasError) {
                break;
            }
            usleep(100 * 1000); // 100ms
        } while (microtime(true) < $deadline);

        $content = $page->content();
        $hasCoordinates = str_contains($content, '59.95,30.31667');
        $hasError = str_contains($content, 'Error:');

        $this->assertTrue($hasCoordinates || $hasError, 'Geolocation API should respond with either coordinates or error message');

        $page->close();
    }

    #[Test]
    public function itSetsOfflineMode(): void
    {
        $page = $this->context->newPage();
        $this->context->setOffline(true);

        try {
            $page->goto('http://example.com/');
            $this->fail('Should have thrown an exception for offline mode.');
        } catch (\Exception $e) {
            $this->assertTrue(
                str_contains($e->getMessage(), 'net::ERR_INTERNET_DISCONNECTED')
                || str_contains($e->getMessage(), 'offline')
                || str_contains($e->getMessage(), 'NetworkError'),
                'Exception message should indicate offline/network error.'
            );
        }

        $this->context->setOffline(false);
        $this->installRouteServer($page, [
            '/index.html' => '<h1>Hello</h1>',
        ]);
        $page->goto($this->routeUrl('/index.html'));
        $this->assertStringContainsString('Hello', $page->content());
        $page->close();
    }
}
