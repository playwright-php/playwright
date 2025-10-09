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

        $this->assertInstanceOf(\Playwright\Network\ResponseInterface::class, $response);
        $this->assertStringContainsString('/page2.html', $response->url());
        $this->assertEquals(200, $response->status());
    }

    #[Test]
    public function itCanGetByText(): void
    {
        $locator = $this->page->getByText('Hello World');
        $this->assertInstanceOf(\Playwright\Locator\LocatorInterface::class, $locator);
        $text = $locator->textContent();
        $this->assertSame('Hello World', $text);
    }

    #[Test]
    public function itCanGetByPlaceholder(): void
    {
        $locator = $this->page->getByPlaceholder('Username');
        $this->assertInstanceOf(\Playwright\Locator\LocatorInterface::class, $locator);
        $placeholder = $locator->getAttribute('placeholder');
        $this->assertSame('Username', $placeholder);
    }

    #[Test]
    public function itCanGetByTitle(): void
    {
        $this->page->evaluate("document.querySelector('h1').setAttribute('title', 'Main Heading')");
        $locator = $this->page->getByTitle('Main Heading');
        $this->assertInstanceOf(\Playwright\Locator\LocatorInterface::class, $locator);
        $text = $locator->textContent();
        $this->assertSame('Hello World', $text);
    }

    #[Test]
    public function itCanGetByTestId(): void
    {
        $this->page->evaluate("document.querySelector('button').setAttribute('data-testid', 'submit-button')");
        $locator = $this->page->getByTestId('submit-button');
        $this->assertInstanceOf(\Playwright\Locator\LocatorInterface::class, $locator);
        $text = $locator->textContent();
        $this->assertSame('Test Button', $text);
    }

    #[Test]
    public function itCanGetByAltText(): void
    {
        $this->page->setContent('<img src="/logo.png" alt="Company Logo" />');
        $locator = $this->page->getByAltText('Company Logo');
        $this->assertInstanceOf(\Playwright\Locator\LocatorInterface::class, $locator);
        $alt = $locator->getAttribute('alt');
        $this->assertSame('Company Logo', $alt);
    }

    #[Test]
    public function itCanInteractWithGetByText(): void
    {
        $this->page->getByText('link')->click();
        $this->assertStringContainsString('page2.html', $this->page->url());
    }

    #[Test]
    public function itCanFillInputUsingGetByPlaceholder(): void
    {
        $this->page->getByPlaceholder('Username')->fill('testuser');
        $value = $this->page->getByPlaceholder('Username')->inputValue();
        $this->assertSame('testuser', $value);
    }

    private static function findFreePort(): int
    {
        return 0;
    }
}
