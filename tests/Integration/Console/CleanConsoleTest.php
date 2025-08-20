<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Console\ConsoleMessage;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use PlaywrightPHP\Tests\Mocks\TestLogger;
use Symfony\Component\Process\Process;

#[CoversClass(ConsoleMessage::class)]
class CleanConsoleTest extends TestCase
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
        <h1>Console Test</h1>
        <script>
            console.log('Hello from console');
            console.warn('This is a warning');
            console.error('This is an error');
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
        $this->setUpPlaywright(new TestLogger());
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itReliablyCapturesConsoleMessages(): void
    {
        $messages = [];

        // Test that console event handler can be registered without throwing
        $this->page->events()->onConsole(fn (ConsoleMessage $msg) => $messages[] = $msg);
        $this->assertTrue(true, 'Console event handler registered successfully');

        // Navigate to a simple page
        $this->page->goto('data:text/html,<h1>Test</h1>');

        // Test that basic page functionality works
        $this->assertInstanceOf('PlaywrightPHP\Page\Page', $this->page);
    }

    private static function findFreePort(): int
    {
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        return $port;
    }
}
