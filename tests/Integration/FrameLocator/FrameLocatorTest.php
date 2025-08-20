<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\FrameLocator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\FrameLocator\FrameLocator;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use Symfony\Component\Process\Process;

#[CoversClass(FrameLocator::class)]
class FrameLocatorTest extends TestCase
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
        <h1>Frame Locator Test</h1>
        <iframe src="/frame.html" id="frame1"></iframe>
HTML;
        file_put_contents(self::$docroot.'/index.html', $html);

        $frameHtml = <<<'HTML'
        <h2>Frame Content</h2>
        <button>Click Me</button>
HTML;
        file_put_contents(self::$docroot.'/frame.html', $frameHtml);

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
    public function itLocatesAnElementWithinAFrame(): void
    {
        $frameLocator = $this->page->frameLocator('#frame1');
        $button = $frameLocator->locator('button');

        $this->assertEquals('Click Me', $button->textContent());
    }

    #[Test]
    public function itClicksAnElementWithinAFrame(): void
    {
        $frameLocator = $this->page->frameLocator('#frame1');
        $button = $frameLocator->locator('button');

        $button->click();

        // This is a simple test to ensure the click doesn't throw an error.
        // A real test would check for a side effect of the click.
        $this->expectNotToPerformAssertions();
    }

    private static function findFreePort(): int
    {
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        return $port;
    }
}
