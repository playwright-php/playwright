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

namespace Playwright\Tests\Functional\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Browser\BrowserContext;
use Playwright\Network\Route;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

/**
 * Alone in its class so it runs on a fresh server, the only place a page route
 * and a context route used to be given the same id.
 */
#[CoversClass(Page::class)]
#[CoversClass(BrowserContext::class)]
#[CoversClass(Route::class)]
final class RouteIdTest extends FunctionalTestCase
{
    public function testPageAndContextRoutesInFlightTogetherAnswerTheirOwnRequests(): void
    {
        $this->goto('/index.html');
        // Neither request is answered until both are waiting on the server.
        $held = [];
        $answerBoth = static function () use (&$held): void {
            if (2 === \count($held)) {
                $held['page']->fulfill(['status' => 200, 'body' => 'from page']);
                $held['context']->fulfill(['status' => 200, 'body' => 'from context']);
            }
        };
        $this->page->route('**/api/data.json', static function (Route $route) use (&$held, $answerBoth): void {
            $held['page'] = $route;
            $answerBoth();
        });
        $this->context->route('**/api/other', static function (Route $route) use (&$held, $answerBoth): void {
            $held['context'] = $route;
            $answerBoth();
        });

        $result = $this->page->evaluate('async () => Promise.all(["/api/data.json", "/api/other"].map(async url => (await fetch(url, { signal: AbortSignal.timeout(5000) })).text()))');

        $this->assertSame(['from page', 'from context'], $result);
    }
}
