<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Page\Page;
use PlaywrightPHP\Page\PageInterface;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;

#[CoversClass(Page::class)]
final class PagePopupTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    public function setUp(): void
    {
        $this->setUpPlaywright();
    }

    #[Test]
    public function itCanWaitForPopupFromPageAction(): void
    {
        $page = $this->browser->newPage();

        // Set up HTML with popup trigger
        $page->setContent('
            <!DOCTYPE html>
            <html>
            <head><title>Main Page</title></head>
            <body>
                <h1>Main Page</h1>
                <button id="popup-btn" onclick="openPopup()">Open Popup</button>
                <script>
                    function openPopup() {
                        console.log("openPopup function called");
                        try {
                            const popup = window.open("about:blank", "_blank", "width=300,height=200");
                            return popup;
                        } catch (error) {
                            console.error("Error in openPopup:", error);
                        }
                    }
                </script>
            </body>
            </html>
        ');

        // Wait for popup when button is clicked
        $popup = $page->waitForPopup(function () use ($page) {
            $page->click('#popup-btn');
        }, ['timeout' => 2000]);

        $this->assertNotNull($popup);
        $this->assertNotSame($page, $popup);

        // Small delay to ensure popup is fully ready
        usleep(100000); // 100ms

        // Test popup operations with debug logging enabled
        $this->assertInstanceOf(PageInterface::class, $popup);

        // This should now show detailed debug output
        $popup->setContent('
            <!DOCTYPE html>
            <html>
            <head><title>Popup Window</title></head>
            <body>
                <h1>This is a popup</h1>
                <p id="popup-text">Popup content</p>
            </body>
            </html>
        ');

        $this->assertEquals('This is a popup', $popup->locator('h1')->innerText());
        $this->assertEquals('Popup content', $popup->locator('#popup-text')->innerText());

        $popup->close();
        $page->close();
    }

    #[Test]
    public function itCanWaitForPopupFromContextAction(): void
    {
        $context = $this->browser->newContext();
        $page = $context->newPage();

        // Set up HTML with popup trigger
        $page->setContent(<<<HTML
            <!DOCTYPE html>
            <html>
            <body>
                <a id="new-tab" href="javascript:void(0)" onclick="window.open('/test.html', '_blank')">
                    Open New Tab
                </a>
            </body>
            </html>
        HTML);

        // Wait for popup at context level
        $popup = $context->waitForPopup(function () use ($page) {
            $page->click('#new-tab');
        });

        $this->assertNotNull($popup);
        $this->assertNotSame($page, $popup);

        // Set content in the popup
        $popup->setContent('<h1>New Tab Content</h1>');
        $this->assertEquals('New Tab Content', $popup->locator('h1')->innerText());

        $popup->close();
        $context->close();
    }

    #[Test]
    public function itCanWaitForEventPopup(): void
    {
        $context = $this->browser->newContext();
        $page = $context->newPage();

        $page->setContent(<<<HTML
            <!DOCTYPE html>
            <html>
            <body>
                <script>
                  setTimeout(() => { window.open('/popup', 'popup'); }, 50);
                </script>
            </body>
            </html>
        HTML);

        // Arm the listener first; the page script will open the popup
        $result = $context->waitForEvent('page', null, 2000);
        $this->assertIsArray($result);

        $context->close();
    }

    #[Test]
    public function itHandlesMultiplePopups(): void
    {
        $page = $this->browser->newPage();

        $page->setContent(<<<HTML
            <!DOCTYPE html>
            <html>
            <body>
                <button id="popup1" onclick="window.open('/popup1', 'p1')">Popup 1</button>
                <button id="popup2" onclick="window.open('/popup2', 'p2')">Popup 2</button>
            </body>
            </html>
        HTML);

        // Open first popup
        $popup1 = $page->waitForPopup(function () use ($page) {
            $page->click('#popup1');
        });

        $popup1->setContent('<title>Popup 1</title><h1>First Popup</h1>');
        $this->assertEquals('First Popup', $popup1->locator('h1')->innerText());

        // Open second popup
        $popup2 = $page->waitForPopup(function () use ($page) {
            $page->click('#popup2');
        });

        $popup2->setContent('<title>Popup 2</title><h1>Second Popup</h1>');
        $this->assertEquals('Second Popup', $popup2->locator('h1')->innerText());

        // Verify both popups are different instances
        $this->assertNotSame($popup1, $popup2);
        $this->assertNotSame($page, $popup1);
        $this->assertNotSame($page, $popup2);

        $popup1->close();
        $popup2->close();
        $page->close();
    }
}
