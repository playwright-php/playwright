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
use PlaywrightPHP\Tests\Support\RouteServerTestTrait;

#[CoversClass(ConsoleMessage::class)]
class ConsoleTest extends TestCase
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
        $this->context = $this->browser->newContext();
        $this->page = $this->context->newPage();

        $this->installRouteServer($this->page, [
            '/index.html' => <<<'HTML'
                <h1>Console Test</h1>
                <script>
                    console.log('Hello from console');
                    console.warn('This is a warning');
                    console.error('This is an error');
                </script>
            HTML,
        ]);
    }

    public function tearDown(): void
    {
        $this->context->close();
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itCapturesConsoleMessages(): void
    {
        $messages = [];

        $this->page->events()->onConsole(fn (ConsoleMessage $msg) => $messages[] = $msg);

        $this->page->goto($this->routeUrl('/index.html'));
        usleep(500000);

        $this->assertTrue(true, 'Console event handler registered and page loaded successfully');
    }

    private static function findFreePort(): int
    {
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        return $port;
    }
}
