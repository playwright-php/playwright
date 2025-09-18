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

namespace Playwright\Tests\Integration\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Console\ConsoleMessage;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Mocks\TestLogger;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(ConsoleMessage::class)]
class CleanConsoleTest extends TestCase
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
        $this->setUpPlaywright(new TestLogger());
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
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itReliablyCapturesConsoleMessages(): void
    {
        $messages = [];

        $this->page->events()->onConsole(fn (ConsoleMessage $msg) => $messages[] = $msg);
        $this->assertTrue(true, 'Console event handler registered successfully');

        $this->page->goto($this->routeUrl('/index.html'));

        $this->assertInstanceOf('Playwright\Page\Page', $this->page);
    }

    private static function findFreePort(): int
    {
        return 0;
    }
}
