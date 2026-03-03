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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\LocatorInterface;
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
                        <textarea aria-hidden="true">Hidden textarea</textarea>
                        <img src="/logo.png" alt="Company Logo" />
                        <a href="/page2.html">link</a>
                        <button id="test-btn" onclick="console.log(\'test\');">Test Button</button>
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

    /**
     * @see Playwright API for getByText: https://playwright.dev/docs/api/class-locator#locator-get-by-text
     *
     * @return array<string, array{input: array{text: string, options?: array<string, mixed>}, assertions: array{count:int, text:string}}>
     */
    public static function getByTextDataProvider(): array
    {
        return [
            'with only text' => ['input' => ['text' => 'Text without a role'], 'assertions' => ['count' => 1, 'text' => 'Text without a role']],
            // TODO: fix this test. The method of passing the name to Playwright is too strict by default
            // 'is case-insensitive by default' => ['input' => ['text' => 'text without a role'], 'assertions' => ['count' => 1, 'text' => 'Text without a role']],
            'with exact true' => ['input' => ['text' => 'Text without a role', 'options' => ['exact' => true]], 'assertions' => ['count' => 1, 'text' => 'Text without a role']],
            // TODO: fix this test. The method of passing the name to Playwright is too strict by default
            // 'with exact false' => ['input' => ['text' => 'Text without', 'options' => ['exact' => false]], 'assertions' => ['count' => 1, 'text' => 'Text without a role']],
        ];
    }

    #[DataProvider('getByTextDataProvider')]
    #[Test]
    public function itCanGetByText(mixed $input, mixed $assertions): void
    {
        $locator = $this->page->getByText($input['text'], $input['options'] ?? []);
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $text = $locator->textContent();
        $this->assertSame($assertions['count'], $locator->count());
        $this->assertSame($assertions['text'], $text);
    }

    /**
     * @see Playwright API for getByRole: https://playwright.dev/docs/api/class-locator#locator-get-by-role
     *
     * @return array<string, array{input: array{role: string, options?: array<string, mixed>}, assertions: array{count:int}}>
     */
    public static function getByRoleDataProvider(): array
    {
        return [
            'with only role' => ['input' => ['role' => 'img'], 'assertions' => ['count' => 1]],
            'with role and name' => ['input' => ['role' => 'button', 'options' => ['name' => 'Test Button']], 'assertions' => ['count' => 1]],
            // TODO: fix this test. The method of passing the name to Playwright is too strict by default
            // 'with name case-insensitive by default' => ['input' => ['role' => 'button', 'options' => ['name' => 'test button']], 'assertions' => ['count' => 1]],
            'with level matching heading' => ['input' => ['role' => 'heading', 'options' => ['level' => 1]], 'assertions' => ['count' => 1]],
            'with level not matching any heading' => ['input' => ['role' => 'heading', 'options' => ['level' => 2]], 'assertions' => ['count' => 0]],
            'with checked true' => ['input' => ['role' => 'checkbox', 'options' => ['checked' => true]], 'assertions' => ['count' => 0]],
            'with disabled true' => ['input' => ['role' => 'button', 'options' => ['disabled' => true]], 'assertions' => ['count' => 0]],
            'with disabled false' => ['input' => ['role' => 'button', 'options' => ['disabled' => false]], 'assertions' => ['count' => 2]],
            'with includeHidden true' => ['input' => ['role' => 'textbox', 'options' => ['includeHidden' => true]], 'assertions' => ['count' => 2]],
            'with expanded true' => ['input' => ['role' => 'button', 'options' => ['expanded' => true]], 'assertions' => ['count' => 0]],
            'with pressed true' => ['input' => ['role' => 'button', 'options' => ['pressed' => true]], 'assertions' => ['count' => 0]],
            'with selected true' => ['input' => ['role' => 'option', 'options' => ['selected' => true]], 'assertions' => ['count' => 0]],
        ];
    }

    #[DataProvider('getByRoleDataProvider')]
    #[Test]
    public function itCanGetByRole(mixed $input, mixed $assertions): void
    {
        $locator = $this->page->getByRole($input['role'], $input['options'] ?? []);
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame($assertions['count'], $locator->count());
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
