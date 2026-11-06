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
use Playwright\Exception\PlaywrightException;
use Playwright\Testing\PlaywrightTestCaseTrait;

#[CoversNothing]
final class BrowserResourceCleanupTest extends TestCase
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

    public function testClosingABrowserRemovesItsPagesWithoutAffectingAnotherBrowser(): void
    {
        $browser = $this->playwright->chromium()->launch();

        try {
            $context = $browser->newContext();
            $pages = [$browser->newPage(), $context->newPage(), $context->newPage()];
            $this->page->setContent('<title>Still open</title>');

            $browser->close();
            $browser->close();

            foreach ($pages as $page) {
                try {
                    $page->title();
                    $this->fail('The closed browser\'s page must no longer be registered in the bridge.');
                } catch (PlaywrightException $e) {
                    $this->assertStringContainsString('Page not found:', $e->getMessage());
                }
            }

            $this->assertTrue($this->browser->isConnected());
            $this->assertSame('Still open', $this->page->title());
            $this->assertSame('about:blank', $this->context->newPage()->url());
        } finally {
            $browser->close();
        }
    }
}
