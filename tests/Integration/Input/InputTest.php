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

namespace Playwright\Tests\Integration\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Input\Keyboard;
use Playwright\Input\Mouse;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

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

    #[Test]
    public function itUsesKeyboardDownAndUp(): void
    {
        $this->page->locator('#input-text')->click();

        $this->page->keyboard()->down('Shift');
        $this->page->keyboard()->press('KeyA');
        $this->page->keyboard()->press('KeyB');
        $this->page->keyboard()->up('Shift');
        $this->page->keyboard()->press('KeyC');

        usleep(100000);
        $value = $this->page->locator('#input-text')->inputValue();
        $this->assertSame('ABc', $value);
    }

    #[Test]
    public function itUsesMouseDoubleClick(): void
    {
        $this->page->evaluate("
            document.getElementById('mouse-tracker').addEventListener('dblclick', (e) => {
                document.getElementById('mouse-tracker').setAttribute('data-dblclicked', 'yes');
            });
        ");

        $this->page->mouse()->dblclick(100, 100);

        usleep(100000);
        $attr = $this->page->evaluate("document.getElementById('mouse-tracker').getAttribute('data-dblclicked')");
        $this->assertSame('yes', $attr);
    }

    #[Test]
    public function itUsesMouseDownAndUp(): void
    {
        $this->page->evaluate("
            let downFired = false;
            let upFired = false;
            document.getElementById('mouse-tracker').addEventListener('mousedown', () => {
                downFired = true;
                document.getElementById('mouse-tracker').setAttribute('data-down', 'yes');
            });
            document.getElementById('mouse-tracker').addEventListener('mouseup', () => {
                upFired = true;
                document.getElementById('mouse-tracker').setAttribute('data-up', 'yes');
            });
        ");

        $this->page->mouse()->move(100, 100);
        $this->page->mouse()->down();
        usleep(50000);

        $downAttr = $this->page->evaluate("document.getElementById('mouse-tracker').getAttribute('data-down')");
        $this->assertSame('yes', $downAttr);

        $this->page->mouse()->up();
        usleep(50000);

        $upAttr = $this->page->evaluate("document.getElementById('mouse-tracker').getAttribute('data-up')");
        $this->assertSame('yes', $upAttr);
    }

    #[Test]
    public function itUsesMouseDownAndUpWithButton(): void
    {
        $this->page->mouse()->move(100, 100);
        $this->page->mouse()->down(['button' => 'left']);
        $this->page->mouse()->up(['button' => 'left']);

        usleep(100000);
        $bgColor = $this->page->evaluate("document.getElementById('mouse-tracker').style.backgroundColor");
        $this->assertSame('lightgreen', $bgColor);
    }

    private static function findFreePort(): int
    {
        return 0;
    }
}
