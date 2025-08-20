<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Network\Route;
use PlaywrightPHP\Network\RouteInterface;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use Symfony\Component\Process\Process;

#[CoversClass(Route::class)]
class NetworkTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    private static ?Process $server = null;
    private static string $docroot;
    private static int $port;

    public static function setUpBeforeClass(): void
    {
        self::$docroot = sys_get_temp_dir().'/playwright-php-tests-'.uniqid('', true);
        mkdir(self::$docroot);

        file_put_contents(self::$docroot.'/index.html', '<h1>Network Test</h1><img src="/image.png">');
        file_put_contents(self::$docroot.'/image.png', 'fake image data');

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
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itCanAbortARequest(): void
    {
        $this->page->route('**/*.png', fn (RouteInterface $route) => $route->abort());
        $response = $this->page->goto('http://localhost:'.self::$port.'/index.html', ['waitUntil' => 'domcontentloaded']);
        $this->assertTrue($response->ok());
    }

    #[Test]
    public function itCanFulfillARequest(): void
    {
        $this->page->route('**/index.html', fn (RouteInterface $route) => $route->fulfill([
            'status' => 201,
            'body' => '<h1>Intercepted</h1>',
        ]));
        $response = $this->page->goto('http://localhost:'.self::$port.'/index.html');
        $this->assertEquals(201, $response->status());
        $this->assertStringContainsString('<h1>Intercepted</h1>', $this->page->content());
    }

    private static function findFreePort(): int
    {
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        return $port;
    }
}
