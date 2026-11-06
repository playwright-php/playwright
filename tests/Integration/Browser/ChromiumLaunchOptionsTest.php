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
final class ChromiumLaunchOptionsTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    protected function setUp(): void
    {
        $this->setUpPlaywright();
    }

    protected function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    public function testCrossSiteFramesRetainFirstPartyStorage(): void
    {
        $page = $this->page;
        $storageUrl = 'https://storage.test/';
        $embeddingUrl = 'https://embedding.test/';
        $page->route($storageUrl, static function (RouteInterface $route): void {
            $route->fulfill(['contentType' => 'text/html', 'body' => '<p>Storage page</p>']);
        });
        $page->route($embeddingUrl, static function (RouteInterface $route) use ($storageUrl): void {
            $route->fulfill(['contentType' => 'text/html', 'body' => '<iframe src="'.$storageUrl.'"></iframe>']);
        });

        $page->goto($storageUrl);
        $page->evaluate('() => localStorage.setItem("key", "first-party value")');
        $page->goto($embeddingUrl);
        $frame = $page->frame(['url' => $storageUrl]);

        $this->assertNotNull($frame);
        $value = $frame->evaluate('() => localStorage.getItem("key")');

        $this->assertSame('first-party value', $value);
    }
}
