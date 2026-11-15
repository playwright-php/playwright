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

namespace Playwright\Tests\Integration\Browser;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Playwright\Network\RouteInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;

#[CoversNothing]
final class CookieDeletionBoundaryTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    protected function setUp(): void
    {
        $this->setUpPlaywright();
        $this->page->route('https://private.example/**', static function (RouteInterface $route): void {
            $route->fulfill(['contentType' => 'text/html', 'headers' => ['access-control-allow-origin' => 'https://private.example'], 'body' => '<p>private-content</p><input id="password">']);
        });
    }

    protected function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    public function testNamedDeletionRemovesPartitionedAndOrdinaryCookies(): void
    {
        $this->context->addCookies([
            ['name' => 'session', 'value' => 'partitioned', 'domain' => 'private.example', 'path' => '/', 'secure' => true, 'sameSite' => 'None', 'partitionKey' => 'https://top.example'],
            ['name' => 'session', 'value' => 'ordinary', 'domain' => 'private.example', 'path' => '/', 'secure' => true],
            ['name' => 'keep', 'value' => 'ordinary', 'domain' => 'private.example', 'path' => '/', 'secure' => true],
        ]);
        $this->assertCount(3, $this->context->cookies());
        $this->context->deleteCookie('session');
        $this->assertSame(['keep'], array_column($this->context->cookies(), 'name'));
    }
}
