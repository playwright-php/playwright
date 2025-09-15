<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Browser\BrowserContextInterface;
use PlaywrightPHP\Exception\TimeoutException;
use PlaywrightPHP\Page\Page;
use PlaywrightPHP\Transport\TransportInterface;

#[CoversClass(Page::class)]
final class PagePopupTest extends TestCase
{
    private TransportInterface $transport;
    private BrowserContextInterface $context;
    private Page $page;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->context = $this->createMock(BrowserContextInterface::class);
        $this->page = new Page($this->transport, $this->context, 'page1');
    }

    public function testWaitForPopupSuccess(): void
    {
        $actionExecuted = false;
        $action = function () use (&$actionExecuted) {
            $actionExecuted = true;
        };

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) use (&$actionExecuted) {
                // Action should be executed before transport call
                $this->assertTrue($actionExecuted);
                return 'page.waitForPopup' === $payload['action']
                    && 'page1' === $payload['pageId']
                    && 30000 === $payload['timeout'];
            }))
            ->willReturn(['popupPageId' => 'popup123']);

        $popup = $this->page->waitForPopup($action);

        $this->assertInstanceOf(Page::class, $popup);
        $this->assertTrue($actionExecuted);
    }

    public function testWaitForPopupWithCustomTimeout(): void
    {
        $actionExecuted = false;
        $action = function () use (&$actionExecuted) {
            $actionExecuted = true;
        };

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) use (&$actionExecuted) {
                $this->assertTrue($actionExecuted);
                return 'page.waitForPopup' === $payload['action']
                    && 5000 === $payload['timeout'];
            }))
            ->willReturn(['popupPageId' => 'popup456']);

        $popup = $this->page->waitForPopup($action, ['timeout' => 5000]);

        $this->assertInstanceOf(Page::class, $popup);
    }

    public function testWaitForPopupTimeout(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('No popup was created within the timeout period');

        $action = function () {};
        $this->page->waitForPopup($action);
    }

    public function testWaitForPopupInvalidResponse(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['popupPageId' => null]);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('No popup was created within the timeout period');

        $action = function () {};
        $this->page->waitForPopup($action);
    }
}
