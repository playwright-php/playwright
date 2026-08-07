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

namespace Playwright\Tests\Integration\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Console\ConsoleMessage;
use Playwright\Network\RequestInterface;
use Playwright\Network\ResponseInterface;
use Playwright\Page\Page;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Page::class)]
class PageTest extends TestCase
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
        $this->installRouteServer($this->page, [
            '/index.html' => '<!DOCTYPE html><html><head><title>Test Page</title></head><body><h1>Hello World</h1><a href="/page2.html">link</a><button id="test-btn" onclick="console.log(\'test\');">Test Button</button><input type="file" id="file-input" /><form id="test-form"><input type="text" name="username" placeholder="Username" /><button type="submit">Submit</button></form><script src="/script.js"></script></body></html>',
            '/page2.html' => '<h2>Page 2</h2>',
            '/script.js' => 'window.myVar = 123; window.testFunction = function(arg) { return "result:" + arg; };',
            '/style.css' => 'h1 { color: red; }',
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itGetsThePageUrl(): void
    {
        $this->assertEquals($this->routeUrl('/index.html'), $this->page->url());
    }

    #[Test]
    public function itGetsThePageTitle(): void
    {
        $this->page->setContent('<title>My Title</title>');
        $this->assertEquals('My Title', $this->page->title());
    }

    #[Test]
    public function itPerformsDragAndDrop(): void
    {
        $this->page->setContent(<<<'HTML'
            <div id="source" draggable="true">Source</div>
            <div id="target">Target</div>
            <script>
                const target = document.querySelector('#target');
                target.addEventListener('dragover', event => event.preventDefault());
                target.addEventListener('drop', event => target.dataset.dropped = 'true');
            </script>
            HTML);

        $this->page->dragAndDrop('#source', '#target');

        $this->assertSame('true', $this->page->locator('#target')->getAttribute('data-dropped'));
    }

    #[Test]
    public function itAddsInitScriptBeforeNavigation(): void
    {
        $this->page->addInitScript('window.pageInit = "ready";');
        $this->page->goto('data:text/html,<title>Init script</title>');

        $this->assertSame('ready', $this->page->evaluate('window.pageInit'));
    }

    #[Test]
    public function itSendsExtraHttpHeaders(): void
    {
        $requestHeaders = [];
        $this->page->events()->onRequest(static function ($request) use (&$requestHeaders): void {
            $requestHeaders = array_change_key_case($request->headers(), CASE_LOWER);
        });
        $this->page->setExtraHTTPHeaders(['X-Page-Test' => 'present']);

        $this->page->goto($this->routeUrl('/page2.html'));

        $this->assertSame('present', $requestHeaders['x-page-test'] ?? null);
    }

    #[Test]
    public function itCanNavigateBackAndForward(): void
    {
        $this->page->click('a');
        $this->page->waitForURL('**/page2.html');
        $this->assertStringContainsString('/page2.html', $this->page->url());

        $this->page->goBack();
        $this->page->waitForURL('**/index.html');
        $this->assertStringContainsString('/index.html', $this->page->url());

        $this->page->goForward();
        $this->page->waitForURL('**/page2.html');
        $this->assertStringContainsString('/page2.html', $this->page->url());
    }

    #[Test]
    public function itReloadsThePage(): void
    {
        $initialUrl = $this->page->url();

        $this->page->reload();

        $this->assertEquals($initialUrl, $this->page->url());
    }

    #[Test]
    public function itSetsTheViewportSize(): void
    {
        $this->page->setViewportSize(800, 600);
        $viewport = $this->page->viewportSize();
        $this->assertEquals(800, $viewport['width']);
        $this->assertEquals(600, $viewport['height']);
    }

    #[Test]
    public function itTakesAScreenshotAndReturnsPath(): void
    {
        $path = $this->page->screenshot();
        $this->assertIsString($path);
        $this->assertNotEmpty($path);

        $this->assertFileExists($path);

        $fileContent = file_get_contents($path);
        $this->assertStringStartsWith(base64_decode('iVBORw0KGgo='), $fileContent);

        unlink($path);
    }

    #[Test]
    public function itAddsAScriptTag(): void
    {
        $this->page->addScriptTag(['url' => '/script.js']);
        $this->assertEquals(123, $this->page->evaluate('window.myVar'));
    }

    #[Test]
    public function itAddsAStyleTag(): void
    {
        $this->page->addStyleTag(['url' => $this->routeUrl('/style.css')]);
        $this->page->waitForSelector('h1');

        $count = $this->page->locator('h1')->count();
        $this->assertGreaterThan(0, $count, 'H1 element should exist');

        $text = $this->page->locator('h1')->textContent();
        $this->assertEquals('Hello World', $text);

        $tagName = $this->page->locator('h1')->evaluate('element => element.tagName');
        $this->assertEquals('H1', $tagName);
        $color = $this->page->locator('h1')->evaluate('element => window.getComputedStyle(element).color');
        $this->assertEquals('rgb(255, 0, 0)', $color);
    }

    #[Test]
    public function itReturnsItsContext(): void
    {
        $this->assertSame($this->context, $this->page->context());
    }

    #[Test]
    public function itWaitsForAResponse(): void
    {
        $response = $this->page->waitForResponse('**/page2.html', ['action' => "document.querySelector('a').click()"]);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertStringContainsString('/page2.html', $response->url());
        $this->assertEquals(200, $response->status());
    }

    #[Test]
    public function itWaitsForAGlobMatchedRequest(): void
    {
        $request = $this->page->waitForRequest('**/page2.html', ['action' => "document.querySelector('a').click()"]);

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertStringContainsString('/page2.html', $request->url());
        $this->assertSame('GET', $request->method());
    }

    #[Test]
    public function itListsRecentRequestSnapshots(): void
    {
        $requests = $this->page->requests();
        $urls = array_map(static fn (RequestInterface $request): string => $request->url(), $requests);

        $this->assertContains($this->routeUrl('/index.html'), $urls);
        $this->assertContains($this->routeUrl('/script.js'), $urls);
    }

    #[Test]
    public function itListsStoredConsoleMessages(): void
    {
        $this->page->evaluate("console.log('stored message from PHP')");

        $messages = $this->page->consoleMessages();
        $texts = array_map(static fn (ConsoleMessage $message): string => $message->text(), $messages);
        $index = array_search('stored message from PHP', $texts, true);

        $this->assertIsInt($index, 'The logged message should be part of the recorded history');
        $this->assertSame('log', $messages[$index]->type());
        $this->assertSame($this->page, $messages[$index]->page());
    }

    #[Test]
    public function itClearsStoredConsoleMessages(): void
    {
        $this->page->evaluate("console.log('about to be dropped')");
        $this->assertNotEmpty($this->page->consoleMessages());

        $result = $this->page->clearConsoleMessages();

        $this->assertSame($this->page, $result);
        $this->assertSame([], $this->page->consoleMessages());
    }

    #[Test]
    public function itClearsStoredPageErrors(): void
    {
        $this->page->evaluate('() => { setTimeout(() => { throw new Error("boom"); }, 0); }');

        $result = $this->page->clearPageErrors();

        $this->assertSame($this->page, $result);
    }

    #[Test]
    public function itWaitsForLoadState(): void
    {
        $this->page->click('a');
        $this->page->waitForLoadState();

        $this->assertStringContainsString('/page2.html', $this->page->url());
    }

    #[Test]
    public function itWaitsForFunction(): void
    {
        $this->page->setViewportSize(500, 500);

        $this->page->waitForFunction(
            'window.innerWidth < 600',
            null,
            ['timeout' => 100, 'polling' => 50]
        );

        $this->assertSame(['width' => 500, 'height' => 500], $this->page->viewportSize());
    }

    #[Test]
    public function itWaitsForFunctionSetTimeout(): void
    {
        $this->page->setContent('<div id="status">loading</div>');
        $this->page->evaluate('() => setTimeout(() => { document.getElementById("status").textContent = "ready"; }, 200)');

        $this->page->waitForFunction(
            'document.querySelector("#status").textContent === "ready"',
            null,
            ['timeout' => 300, 'polling' => 50]
        );

        $this->assertSame('ready', $this->page->evaluate('() => document.querySelector("#status").textContent'));
    }

    #[Test]
    public function itWaitsForFunctionWithRafPolling(): void
    {
        $this->page->setContent('<div id="x">0</div><script>requestAnimationFrame(() => { document.getElementById("x").textContent = "1"; });</script>');

        $this->page->waitForFunction(
            '() => document.querySelector("#x") && document.querySelector("#x").textContent === "1"',
            null,
            ['timeout' => 500, 'polling' => 'raf']
        );

        $this->assertSame('1', $this->page->evaluate('() => document.querySelector("#x").textContent'));
    }

    #[Test]
    public function itWaitsForFunctionWithArgument(): void
    {
        $this->page->setContent('<div id="status">loading</div>');
        $this->page->evaluate('() => setTimeout(() => { document.getElementById("status").textContent = "ready"; }, 100)');

        $this->page->waitForFunction(
            'arg => {
                const el = document.getElementById(arg.selector);
                return !!el && el.textContent === arg.text;
            }',
            ['selector' => 'status', 'text' => 'ready'],
            ['timeout' => 300, 'polling' => 50]
        );

        $this->assertSame('ready', $this->page->evaluate('() => document.querySelector("#status").textContent'));
    }

    #[Test]
    public function itThrowsExceptionOnWaitForFunctionTimeout(): void
    {
        $this->expectException(\Playwright\Exception\TimeoutException::class);
        $this->expectExceptionMessage('page.waitForFunction: Timeout 100ms exceeded.');

        $this->page->waitForFunction(
            '() => false',
            null,
            ['timeout' => 100]
        );
    }

    #[Test]
    public function itThrowsExceptionOnInvalidWaitForFunctionPolling(): void
    {
        $this->expectException(\Playwright\Exception\PlaywrightException::class);
        $this->expectExceptionMessage('Unknown polling option: invalid');

        $this->page->waitForFunction(
            '() => true',
            null,
            ['polling' => 'invalid']
        );
    }
}
