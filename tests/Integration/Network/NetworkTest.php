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
use PlaywrightPHP\Tests\Support\HttpServerTestTrait;

#[CoversClass(Route::class)]
class NetworkTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use HttpServerTestTrait;

    public static function setUpBeforeClass(): void
    {
        self::startHttpServer([
            'index.html' => '<h1>Network Test</h1><img src="/image.png">',
            'image.png' => 'fake image data',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        self::stopHttpServer();
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
        $response = $this->page->goto(self::getServerUrl('index.html'), ['waitUntil' => 'domcontentloaded']);
        $this->assertTrue($response->ok());
    }

    #[Test]
    public function itCanFulfillARequest(): void
    {
        $this->page->route('**/index.html', fn (RouteInterface $route) => $route->fulfill([
            'status' => 201,
            'body' => '<h1>Intercepted</h1>',
        ]));
        $response = $this->page->goto(self::getServerUrl('index.html'));
        $this->assertEquals(201, $response->status());
        $this->assertStringContainsString('<h1>Intercepted</h1>', $this->page->content());
    }
}
