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
use Playwright\Network\Request;
use Playwright\Network\RequestInterface;
use Playwright\Network\Response;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Response::class)]
#[CoversClass(Request::class)]
final class NetworkAccessorsTest extends TestCase
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
    public function itReportsTheHttpVersion(): void
    {
        $this->installRouteServer($this->page, [
            '/version.html' => '<h1>version</h1>',
        ]);

        $response = $this->page->goto($this->routeUrl('/version.html'));

        $this->assertNotNull($response);
        $this->assertSame('HTTP/1.1', $response->httpVersion());
    }

    #[Test]
    public function itHasNoExistingResponseWhileTheRequestIsInFlight(): void
    {
        $this->installRouteServer($this->page, [
            '/inflight.html' => '<h1>inflight</h1>',
        ]);

        $seen = [];
        $this->page->events()->onRequest(static function (RequestInterface $request) use (&$seen): void {
            $seen[] = $request->existingResponse();
        });

        $this->page->goto($this->routeUrl('/inflight.html'));

        $this->assertNotEmpty($seen);
        foreach ($seen as $existingResponse) {
            $this->assertNull($existingResponse);
        }
    }
}
