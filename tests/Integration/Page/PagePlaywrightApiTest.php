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
use Playwright\Locator\LocatorInterface;
use Playwright\Network\ResponseInterface;
use Playwright\Page\Page;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Page::class)]
class PagePlaywrightApiTest extends TestCase
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
            '/index.html' => '
                <!DOCTYPE html>
                <html>
                    <head>
                        <title>Test Page</title>
                    </head>
                    <body>
                        <h1>Hello World</h1>
                        <div>Text without a role</div>
                        <span title="Span with title">Titled span</span>
                        <strong data-testid="strong-test-id">Strong with a test id</strong>
                        <img src="/logo.png" alt="Company Logo" />
                        <a href="/page2.html">link</a>
                        <button id="test-btn" onclick="console.log(\'test\');">Test Button</button>
                        <input type="file" id="file-input" />
                        <form id="test-form">
                            <input type="text" name="username" placeholder="Username" />
                            <button type="submit">Submit</button>
                        </form>
                        <script src="/script.js"></script>
                    </body>
                </html>',
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
    public function itCanGetByText(): void
    {
        $locator = $this->page->getByText('Text without a role');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $text = $locator->textContent();
        $this->assertSame('Text without a role', $text);
    }

    #[Test]
    public function itCanGetByRole(): void
    {
        $locator = $this->page->getByRole('img');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame(1, $locator->count());
    }

    #[Test]
    public function itCanGetByPlaceholder(): void
    {
        $locator = $this->page->getByPlaceholder('Username');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $placeholder = $locator->getAttribute('placeholder');
        $this->assertSame('Username', $placeholder);
    }

    #[Test]
    public function itCanGetByTitle(): void
    {
        $locator = $this->page->getByTitle('Span with title');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $text = $locator->textContent();
        $this->assertSame('Titled span', $text);
    }

    #[Test]
    public function itCanGetByTestId(): void
    {
        $locator = $this->page->getByTestId('strong-test-id');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $text = $locator->textContent();
        $this->assertSame('Strong with a test id', $text);
    }

    #[Test]
    public function itCanGetByAltText(): void
    {
        $locator = $this->page->getByAltText('Company Logo');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
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
}
