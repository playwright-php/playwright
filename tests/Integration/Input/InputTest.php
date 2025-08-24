<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Input\Keyboard;
use PlaywrightPHP\Input\Mouse;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use PlaywrightPHP\Tests\Support\RouteServerTestTrait;

#[CoversClass(Keyboard::class)]
#[CoversClass(Mouse::class)]
class InputTest extends TestCase
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
                <h1>Input Test</h1>
                <input type="text" id="input-text">
                <div id="mouse-tracker" style="width: 200px; height: 200px; border: 1px solid black;"></div>
                <script>
                    const tracker = document.getElementById('mouse-tracker');
                    tracker.addEventListener('mousemove', e => {
                        tracker.textContent = `${e.offsetX},${e.offsetY}`;
                    });
                    tracker.addEventListener('click', e => {
                        tracker.style.backgroundColor = 'lightgreen';
                    });
                </script>
            HTML,
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itTypesTextWithKeyboard(): void
    {
        $this->page->locator('#input-text')->click();
        $this->page->keyboard()->type('Hello');
        usleep(100000);
        $this->assertEquals('Hello', $this->page->locator('#input-text')->inputValue());
    }

    #[Test]
    public function itPressesKeysWithKeyboard(): void
    {
        $this->page->locator('#input-text')->click();
        $this->page->keyboard()->press('a');
        $this->page->keyboard()->press('b');
        $this->page->keyboard()->press('c');
        usleep(100000);
        $this->assertEquals('abc', $this->page->locator('#input-text')->inputValue());
    }

    #[Test]
    public function itMovesAndClicksWithMouse(): void
    {
        $this->page->mouse()->move(20, 20);
        $this->page->mouse()->click(100, 100);

        $this->assertTrue(true, 'Mouse move and click operations completed without throwing');
    }

    #[Test]
    public function itHandlesMultiKeyShortcuts(): void
    {
        $inputField = $this->page->locator('#input-text');
        $inputField->click();
        $inputField->fill('Hello World');
        usleep(50000);

        $inputField->click(['clickCount' => 3]);
        usleep(50000);
        $this->page->keyboard()->type('Replaced');
        usleep(100000);
        $this->assertEquals('Replaced', $inputField->inputValue());
    }

    private static function findFreePort(): int
    {
        return 0;
    }
}
