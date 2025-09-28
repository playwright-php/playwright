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
use Playwright\Page\PageInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;

#[CoversClass(BrowserContext::class)]
final class BrowserContextWaitForPopupTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    protected function setUp(): void
    {
        $this->setUpPlaywright();
    }

    #[Test]
    public function itCreatesPopupFromContextWaitForPopup(): void
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

        $popup = $context->waitForPopup(function () use ($page): void {
            $page->click('#open');
        }, ['timeout' => 2000]);

        $this->assertInstanceOf(PageInterface::class, $popup);

        $context->close();
    }
}
