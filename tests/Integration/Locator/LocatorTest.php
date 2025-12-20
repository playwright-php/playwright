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

namespace Playwright\Tests\Integration\Locator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\Locator;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Locator::class)]
class LocatorTest extends TestCase
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
            '/index.html' => <<<'HTML'
                <h1>Locator Test</h1>
                <input type="text" id="input-text" value="initial value">
                <input type="checkbox" id="input-checkbox">
                <button id="button-1">Button 1</button>
                <button id="button-2" disabled>Button 2</button>
                <div id="div-1" style="width: 50px; height: 50px; background-color: blue;"></div>
                <div id="div-2" style="display: none;">Hidden Div</div>
                <select id="select-1">
                    <option value="opt1">Option 1</option>
                    <option value="opt2">Option 2</option>
                </select>
                <p>First paragraph.</p>
                <p>Second paragraph.</p>
            HTML,
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itPerformsClicks(): void
    {
        $this->page->locator('#button-1')->click();
        $this->page->locator('#button-1')->dblclick();
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function itClearsAndFillsInput(): void
    {
        $locator = $this->page->locator('#input-text');
        $this->assertEquals('initial value', $locator->inputValue());
        $locator->clear();
        $this->assertEquals('', $locator->inputValue());
        $locator->fill('new value');
        $this->assertEquals('new value', $locator->inputValue());
    }

    #[Test]
    public function itChecksVisibility(): void
    {
        $this->assertTrue($this->page->locator('#div-1')->isVisible());
        $this->assertFalse($this->page->locator('#div-2')->isVisible());
        $this->assertTrue($this->page->locator('#div-2')->isHidden());
    }

    #[Test]
    public function itChecksEnabledState(): void
    {
        $this->assertTrue($this->page->locator('#button-1')->isEnabled());
        $this->assertFalse($this->page->locator('#button-2')->isEnabled());
        $this->assertTrue($this->page->locator('#button-2')->isDisabled());
    }

    #[Test]
    public function itChecksCheckedState(): void
    {
        $locator = $this->page->locator('#input-checkbox');
        $this->assertFalse($locator->isChecked());
        $locator->check();
        $this->assertTrue($locator->isChecked());
        $locator->uncheck();
        $this->assertFalse($locator->isChecked());
    }

    #[Test]
    public function itGetsTextContent(): void
    {
        $this->assertEquals('Button 1', $this->page->locator('#button-1')->textContent());
        $this->assertEquals('Second paragraph.', $this->page->locator('p')->last()->textContent());
    }

    #[Test]
    public function itGetsInnerTextAndHtml(): void
    {
        $this->assertEquals('Locator Test', $this->page->locator('h1')->innerText());
        $this->assertEquals('Locator Test', $this->page->locator('h1')->innerHTML());
    }

    #[Test]
    public function itGetsAnAttribute(): void
    {
        $this->assertEquals('input-text', $this->page->locator('#input-text')->getAttribute('id'));
        $this->assertNull($this->page->locator('#input-text')->getAttribute('non-existent'));
    }

    #[Test]
    public function itCountsElements(): void
    {
        $this->assertEquals(2, $this->page->locator('p')->count());
    }

    #[Test]
    public function itSelectsOptions(): void
    {
        $locator = $this->page->locator('#select-1');
        $result = $locator->selectOption('opt2');
        $this->assertEquals(['opt2'], $result);
        $this->assertEquals('opt2', $locator->inputValue());
    }

    #[Test]
    public function itWaitsForElement(): void
    {
        $this->page->locator('#div-1')->waitFor();
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function itHoversOverAnElement(): void
    {
        $locator = $this->page->locator('#div-1');

        $locator->hover();

        $this->assertTrue($locator->isVisible());
    }

    #[Test]
    public function itTakesAScreenshotOfAnElement(): void
    {
        $binary = $this->page->locator('#div-1')->screenshot();
        $this->assertIsString($binary);
        $this->assertNotEmpty($binary);

        $this->assertStringStartsWith(base64_decode('iVBORw0KGgo='), base64_decode($binary));
    }

    #[Test]
    public function itEvaluatesElementTagNameAndCss(): void
    {
        $tagName = $this->page->locator('h1')->evaluate('element => element.tagName');
        $this->assertEquals('H1', $tagName);

        $width = $this->page->locator('#div-1')->evaluate('element => window.getComputedStyle(element).width');
        $this->assertEquals('50px', $width);
    }

    #[Test]
    public function itBlursAnElement(): void
    {
        $this->page->evaluate(<<<'JS'
            () => {
                const el = document.querySelector('#input-text');
                if (el) {
                    el.addEventListener('blur', () => { document.body.dataset.blurred = '1'; }, { once: true });
                }
            }
        JS);

        $locator = $this->page->locator('#input-text');
        $locator->focus();
        $locator->blur();

        $blurred = $this->page->evaluate('() => document.body.dataset.blurred || null');
        $this->assertSame('1', $blurred);
    }

    #[Test]
    public function itPerformsDragAndDrop(): void
    {
        // Add drag and drop HTML elements
        $this->page->evaluate(<<<'JS'
            () => {
                const container = document.createElement('div');
                container.innerHTML = `
                    <div id="draggable" 
                         draggable="true" 
                         style="width: 50px; height: 50px; background: red; margin: 10px;"
                         data-status="source">
                        Drag me
                    </div>
                    <div id="dropzone" 
                         style="width: 150px; height: 150px; background: lightblue; margin: 10px; border: 2px dashed #ccc;"
                         data-status="target">
                        Drop here
                    </div>
                `;
                
                const dropzone = container.querySelector('#dropzone');
                dropzone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                });
                
                dropzone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    const draggable = document.querySelector('#draggable');
                    dropzone.appendChild(draggable);
                    dropzone.dataset.status = 'dropped';
                });
                
                document.body.appendChild(container);
            }
        JS);

        $draggable = $this->page->locator('#draggable');
        $dropzone = $this->page->locator('#dropzone');

        // Verify initial state
        $this->assertEquals('source', $draggable->getAttribute('data-status'));
        $this->assertEquals('target', $dropzone->getAttribute('data-status'));

        // Perform drag and drop
        $draggable->dragTo($dropzone);

        // Verify the drop was successful
        $this->assertEquals('dropped', $dropzone->getAttribute('data-status'));
    }

    #[Test]
    public function itPerformsDragAndDropWithOptions(): void
    {
        // Add drag and drop HTML elements
        $this->page->evaluate(<<<'JS'
            () => {
                const container = document.createElement('div');
                container.innerHTML = `
                    <div id="precise-draggable" 
                         draggable="true" 
                         style="width: 100px; height: 100px; background: green; margin: 20px;"
                         data-drag-count="0">
                        Precise drag
                    </div>
                    <div id="precise-dropzone" 
                         style="width: 200px; height: 200px; background: lightyellow; margin: 20px; border: 2px solid #999;"
                         data-drops="0">
                        Precise drop zone
                    </div>
                `;
                
                const dropzone = container.querySelector('#precise-dropzone');
                dropzone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                });
                
                dropzone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    const currentDrops = parseInt(dropzone.dataset.drops) + 1;
                    dropzone.dataset.drops = currentDrops.toString();
                });
                
                document.body.appendChild(container);
            }
        JS);

        $draggable = $this->page->locator('#precise-draggable');
        $dropzone = $this->page->locator('#precise-dropzone');

        // Perform drag and drop with specific positions
        $draggable->dragTo($dropzone, [
            'sourcePosition' => ['x' => 50, 'y' => 50], // center of draggable
            'targetPosition' => ['x' => 100, 'y' => 100], // center of dropzone
        ]);

        // Verify the drop was successful
        $this->assertEquals('1', $dropzone->getAttribute('data-drops'));
    }

    #[Test]
    public function itUsesGetByTextChaining(): void
    {
        $this->page->setContent('<div class="container"><button>Submit</button></div>');
        $locator = $this->page->locator('.container')->getByText('Submit');
        $this->assertSame('Submit', $locator->textContent());
    }

    #[Test]
    public function itUsesGetByRoleChaining(): void
    {
        $this->page->setContent('<div class="form"><button role="button">Click</button></div>');
        $locator = $this->page->locator('.form')->getByRole('button');
        $this->assertSame('Click', $locator->textContent());
    }

    #[Test]
    public function itUsesGetByPlaceholderChaining(): void
    {
        $this->page->setContent('<form><input placeholder="Enter email" /></form>');
        $locator = $this->page->locator('form')->getByPlaceholder('Enter email');
        $locator->fill('test@example.com');
        $this->assertSame('test@example.com', $locator->inputValue());
    }

    #[Test]
    public function itUsesGetByTestIdChaining(): void
    {
        $this->page->setContent('<div class="wrapper"><span data-testid="status">Active</span></div>');
        $locator = $this->page->locator('.wrapper')->getByTestId('status');
        $this->assertSame('Active', $locator->textContent());
    }

    #[Test]
    public function itUsesGetByAltTextChaining(): void
    {
        $this->page->setContent('<div class="images"><img src="/test.png" alt="Test Image" /></div>');
        $locator = $this->page->locator('.images')->getByAltText('Test Image');
        $this->assertSame('Test Image', $locator->getAttribute('alt'));
    }

    #[Test]
    public function itUsesGetByTitleChaining(): void
    {
        $this->page->setContent('<nav><a href="#" title="Home Page">Home</a></nav>');
        $locator = $this->page->locator('nav')->getByTitle('Home Page');
        $this->assertSame('Home', $locator->textContent());
    }

    #[Test]
    public function testClickWithOptionsObject(): void
    {
        $this->page->setContent('<button id="btn" onclick="window.clicked = (window.clicked || 0) + 1">Click me</button>');
        $locator = $this->page->locator('#btn');

        $locator->click(new \Playwright\Locator\Options\ClickOptions(clickCount: 2));

        $clicked = $this->page->evaluate('window.clicked');
        $this->assertEquals(2, $clicked);
    }

    #[Test]
    public function testTypeWithOptionsObject(): void
    {
        $this->page->setContent('<input id="input" type="text" />');
        $locator = $this->page->locator('#input');

        $locator->type('hello', new \Playwright\Locator\Options\TypeOptions(delay: 10.0));

        $this->assertEquals('hello', $locator->inputValue());
    }

    #[Test]
    public function testUncheckWithOptionsObject(): void
    {
        $this->page->setContent('<input id="checkbox" type="checkbox" checked />');
        $locator = $this->page->locator('#checkbox');

        $locator->uncheck(new \Playwright\Locator\Options\UncheckOptions(force: true));

        $this->assertFalse($locator->isChecked());
    }

    private static function findFreePort(): int
    {
        return 0;
    }
}
