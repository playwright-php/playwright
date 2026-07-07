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

namespace Playwright\Tests\Integration\Popup;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContext;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;

/**
 * Regression test: a page opened by the browser itself (window.open()/target="_blank")
 * must become visible through BrowserContext::pages() even when nothing armed a
 * waitForPopup()/waitForEvent('page') listener before the triggering action ran.
 *
 * This is the scenario driver code built on top of Mink hits: it clicks a link and only
 * afterwards inspects the set of open windows/tabs, with no opportunity to arm a listener
 * around the specific click that happens to open one.
 */
#[CoversClass(BrowserContext::class)]
final class PassivePopupDiscoveryTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    protected function setUp(): void
    {
        $this->setUpPlaywright();
    }

    #[Test]
    public function itDiscoversATargetBlankPageWithoutAnArmedListener(): void
    {
        $context = $this->browser->newContext();
        $page = $context->newPage();

        $page->setContent(<<<HTML
            <!DOCTYPE html>
            <html>
            <body>
                <a id="open" href="about:blank" target="_blank">Open</a>
            </body>
            </html>
        HTML);

        self::assertCount(1, $context->pages());

        // No waitForPopup()/waitForEvent('page') armed beforehand - this is the passive
        // discovery path that used to depend on the (missing) server-side subscription.
        $page->click('#open');

        self::assertCount(2, $this->waitForPageCount($context, 2), 'New tab was not discovered passively after the click.');

        $context->close();
    }

    /**
     * @return array<\Playwright\Page\PageInterface>
     */
    private function waitForPageCount(BrowserContextInterface $context, int $expectedCount, int $timeoutMs = 2000): array
    {
        $deadline = microtime(true) + $timeoutMs / 1000;
        do {
            $pages = $context->pages();
            if (count($pages) >= $expectedCount) {
                return $pages;
            }
            usleep(50000);
            // Any transport round trip flushes buffered server-pushed events.
            $context->cookies();
        } while (microtime(true) < $deadline);

        return $context->pages();
    }
}
