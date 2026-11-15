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
use Playwright\Exception\TimeoutException;
use Playwright\Network\RouteInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;

#[CoversNothing]
final class BrowserBoundaryTest extends TestCase
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

    public function testDefaultLaunchKeepsCrossOriginReadsBlocked(): void
    {
        $result = $this->page->evaluate('async () => { try { return await (await fetch("https://private.example/data")).text(); } catch { return "blocked"; } }');
        $this->assertSame('blocked', $result);
        $this->page->goto('https://private.example/data');
        $this->assertSame('private-content', $this->page->locator('p')->textContent());
    }

    public function testWaitPredicatesKeepExpressionsFunctionsAndArguments(): void
    {
        $this->page->setContent('<title>loading</title><script>setTimeout(() => document.title = "ready", 100)</script>');
        $this->page->waitForFunction('(expected) => document.title === expected', 'ready', ['timeout' => 1000, 'polling' => 20]);
        $this->page->waitForFunction('document.title === "ready"', null, ['timeout' => 1000]);
        $this->page->waitForFunction('return document.title === arg;', 'ready', ['timeout' => 1000]);
        $this->assertSame('ready', $this->page->title());
    }

    public function testWaitPredicatesWorkWithRestrictiveContentSecurityPolicy(): void
    {
        $this->page->route('https://private.example/csp', static function (RouteInterface $route): void {
            $route->fulfill(['contentType' => 'text/html', 'headers' => ['content-security-policy' => "default-src 'none'; script-src 'none'"], 'body' => '<title>ready</title>']);
        });
        $this->page->goto('https://private.example/csp');
        $this->page->waitForFunction('() => document.title === "ready"', null, ['timeout' => 1000]);
        $this->assertSame('ready', $this->page->title());
    }

    public function testFalseFunctionPredicatesStillTimeOut(): void
    {
        $this->expectException(TimeoutException::class);
        $this->page->waitForFunction('() => false', null, ['timeout' => 50]);
    }
}
