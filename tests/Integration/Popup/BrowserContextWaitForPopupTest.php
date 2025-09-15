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
use PlaywrightPHP\Browser\BrowserContext;
use PlaywrightPHP\Page\PageInterface;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;

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
