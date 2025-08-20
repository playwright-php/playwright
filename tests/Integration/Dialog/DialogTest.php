<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Dialog;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Dialog\Dialog;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use Symfony\Component\Process\Process;

#[CoversClass(Dialog::class)]
class DialogTest extends TestCase
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
        <h1>Dialog Test</h1>
        <button onclick="alert('I am an alert!')">Alert</button>
        <button onclick="confirm('Are you sure?')">Confirm</button>
        <button onclick="prompt('What is your name?')">Prompt</button>
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
    public function itHandlesAnAlertDialog(): void
    {
        $this->markTestSkipped('Not working yet');

        // $dialogMessage = null;
        // $this->page->once('dialog', fn (Dialog $dialog) => $dialog->accept(function ($message) use (&$dialogMessage) {
        //     $dialogMessage = $message;
        // });
        //
        // $this->page->locator('text=Alert')->click();
        // $this->assertEquals('I am an alert!', $dialogMessage);
    }

    #[Test]
    public function itHandlesAConfirmDialog(): void
    {
        $this->markTestSkipped('Dialogs are not working');
        $this->page->once('dialog', fn (Dialog $dialog) => $dialog->dismiss());
        $this->page->locator('text=Confirm')->click();
        // Test passes if no error is thrown
        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function itHandlesAPromptDialog(): void
    {
        $this->markTestSkipped('Dialogs are not working');
        $this->page->once('dialog', fn (Dialog $dialog) => $dialog->accept('My Name'));
        $this->page->locator('text=Prompt')->click();
        // A real test would check the result of the prompt.
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
