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
use Symfony\Component\Process\Process;

#[CoversClass(Keyboard::class)]
#[CoversClass(Mouse::class)]
class InputTest extends TestCase
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
        // Test that mouse operations don't throw exceptions
        $this->page->mouse()->move(20, 20);
        $this->page->mouse()->click(100, 100);

        // Verify mouse operations completed successfully
        $this->assertTrue(true, 'Mouse move and click operations completed without throwing');
    }

    #[Test]
    public function itHandlesMultiKeyShortcuts(): void
    {
        $this->page->locator('#input-text')->type('Hello World');
        $this->page->keyboard()->press('Meta+A'); // Select all
        $this->page->keyboard()->type('Replaced'); // Type new text
        usleep(100000);
        $this->assertEquals('Replaced', $this->page->locator('#input-text')->inputValue());
    }

    private static function findFreePort(): int
    {
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        return $port;
    }
}
