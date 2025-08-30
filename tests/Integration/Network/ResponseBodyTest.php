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
use PlaywrightPHP\Network\Response;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use PlaywrightPHP\Tests\Support\RouteServerTestTrait;

#[CoversClass(Response::class)]
final class ResponseBodyTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    public function setUp(): void
    {
        $this->setUpPlaywright();
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itFetchesTextBody(): void
    {
        $this->installRouteServer($this->page, [
            '/hello.txt' => 'hello world',
        ]);

        $response = $this->page->goto($this->routeUrl('/hello.txt'));
        $this->assertNotNull($response);
        $this->assertEquals(200, $response->status());
        $this->assertSame('hello world', $response->text());
    }

    #[Test]
    public function itParsesJsonBody(): void
    {
        $this->installRouteServer($this->page, [
            '/data.json' => json_encode(['foo' => 'bar', 'n' => 3]),
        ]);

        $response = $this->page->goto($this->routeUrl('/data.json'));
        $this->assertNotNull($response);
        $this->assertTrue($response->ok());
        $json = $response->json();
        $this->assertSame('bar', $json['foo'] ?? null);
        $this->assertSame(3, $json['n'] ?? null);
    }
}
