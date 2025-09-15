<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Popup;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Page\Page;
use PlaywrightPHP\Page\PageInterface;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;

#[CoversClass(Page::class)]
final class PageWaitForPopupTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    protected function setUp(): void
    {
        $this->setUpPlaywright();
    }

    #[Test]
    public function itCreatesPopupFromPageWaitForPopup(): void
    {
        $page = $this->browser->newPage();

        $page->setContent(<<<HTML
            <!DOCTYPE html>
            <html>
            <body>
                <a id="open" href="about:blank" target="_blank">Open</a>
            </body>
            </html>
        HTML);

        $popup = $page->waitForPopup(function () use ($page): void {
            $page->click('#open');
        }, ['timeout' => 2000]);

        $this->assertInstanceOf(PageInterface::class, $popup);

        $page->close();
    }
}
