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
use PlaywrightPHP\Browser\BrowserContext;
use PlaywrightPHP\Network\RouteInterface;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use PlaywrightPHP\Tests\Support\RouteServerTestTrait;

#[CoversClass(BrowserContext::class)]
final class ContextRouteTest extends TestCase
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
    public function itCanFulfillRequestsAtContextLevel(): void
    {
        // Install a simple route server at context level
        $this->installContextRouteServer($this->context, [
            '/index.html' => '<h1>CTX Route</h1>',
            '/data.json' => json_encode(['ok' => true, 'items' => [1, 2, 3]]),
        ]);

        $response = $this->page->goto($this->routeUrl('/index.html'));
        $this->assertEquals(200, $response?->status());
        $this->assertStringContainsString('CTX Route', $this->page->content() ?? '');

        // Fetch JSON and assert it is intercepted by context route
        $count = $this->page->evaluate(<<<'JS'
            async () => {
                const res = await fetch('/data.json');
                const json = await res.json();
                return Array.isArray(json.items) ? json.items.length : 0;
            }
        JS);

        $this->assertSame(3, $count);
    }

    #[Test]
    public function pageLevelRoutesOverrideContextRoutes(): void
    {
        // Context-level will serve default content
        $this->installContextRouteServer($this->context, [
            '/index.html' => '<h1>CTX</h1>',
        ]);

        // Page-level override
        $this->page->route('**/index.html', function (RouteInterface $route): void {
            $route->fulfill([
                'status' => 200,
                'body' => '<h1>PAGE</h1>',
            ]);
        });

        $this->page->goto($this->routeUrl('/index.html'));
        $this->assertStringContainsString('<h1>PAGE</h1>', $this->page->content() ?? '');
    }
}
