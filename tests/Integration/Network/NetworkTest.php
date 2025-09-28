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

namespace Playwright\Tests\Integration\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Network\Route;
use Playwright\Network\RouteInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Route::class)]
class NetworkTest extends TestCase
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
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itCanAbortARequest(): void
    {
        $this->page->route('**/*.png', fn (RouteInterface $route) => $route->abort());

        $html = '<h1>Network Test</h1><img src="'.$this->routeUrl('/image.png').'">';
        $this->page->goto('data:text/html,'.rawurlencode($html), ['waitUntil' => 'domcontentloaded']);
        $this->assertStringContainsString('Network Test', $this->page->content());
    }

    #[Test]
    public function itCanFulfillARequest(): void
    {
        $this->page->route('**/index.html', fn (RouteInterface $route) => $route->fulfill([
            'status' => 201,
            'body' => '<h1>Intercepted</h1>',
        ]));
        $response = $this->page->goto($this->routeUrl('/index.html'));
        $this->assertEquals(201, $response->status());
        $this->assertStringContainsString('<h1>Intercepted</h1>', $this->page->content());
    }
}
