<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Locator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Locator\Locator;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use Symfony\Component\Process\Process;

#[CoversClass(Locator::class)]
class LocatorTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    private static ?Process $server = null;
    private static string $docroot;
    private static int $port;

    public static function setUpBeforeClass(): void
    {
        self::$docroot = sys_get_temp_dir().'/playwright-php-tests-'.uniqid('', true);
        mkdir(self::$docroot);

        $html = <<<'HTML'
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
HTML;
        file_put_contents(self::$docroot.'/index.html', $html);

        self::$port = self::findFreePort();
        self::$server = new Process(['php', '-S', 'localhost:'.self::$port, '-t', self::$docroot]);
        self::$server->start();
        usleep(100000);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$server && self::$server->isRunning()) {
            self::$server->stop();
        }
        array_map('unlink', glob(self::$docroot.'/*.*'));
        rmdir(self::$docroot);
    }

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->page->goto('http://localhost:'.self::$port.'/index.html');
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
        // Test that hover method executes without throwing
        $locator->hover();
        // Verify that the element is visible after hover
        $this->assertTrue($locator->isVisible());
    }

    #[Test]
    public function itTakesAScreenshotOfAnElement(): void
    {
        $binary = $this->page->locator('#div-1')->screenshot();
        $this->assertIsString($binary);
        $this->assertNotEmpty($binary);
        // Check for PNG header
        $this->assertStringStartsWith(base64_decode('iVBORw0KGgo='), base64_decode($binary));
    }

    private static function findFreePort(): int
    {
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        return $port;
    }
}
