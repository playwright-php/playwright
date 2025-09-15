<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Browser\BrowserContext;
use PlaywrightPHP\Exception\TimeoutException;
use PlaywrightPHP\Page\PageInterface;
use PlaywrightPHP\Transport\TransportInterface;

#[CoversClass(BrowserContext::class)]
final class BrowserContextPopupTest extends TestCase
{
    private TransportInterface $transport;
    private BrowserContext $context;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->context = new BrowserContext($this->transport, 'ctx1');
    }

    public function testWaitForEventPopup(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'context.waitForEvent' === $payload['action']
                    && 'ctx1' === $payload['contextId']
                    && 'popup' === $payload['event'];
            }))
            ->willReturn(['eventData' => ['pageId' => 'popup123']]);

        $result = $this->context->waitForEvent('popup');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('eventData', $result);
    }

    public function testWaitForEventPage(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'context.waitForEvent' === $payload['action']
                    && 'page' === $payload['event']
                    && 15000 === $payload['timeout'];
            }))
            ->willReturn(['eventData' => ['pageId' => 'page456']]);

        $result = $this->context->waitForEvent('page', null, 15000);

        $this->assertIsArray($result);
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
                $this->assertTrue($actionExecuted);
                return 'context.waitForPopup' === $payload['action']
                    && 'ctx1' === $payload['contextId']
                    && 30000 === $payload['timeout'];
            }))
            ->willReturn(['popupPageId' => 'popup789']);

        $popup = $this->context->waitForPopup($action);

        $this->assertInstanceOf(PageInterface::class, $popup);
        $this->assertTrue($actionExecuted);
    }

    public function testWaitForPopupWithOptions(): void
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
                return 'context.waitForPopup' === $payload['action']
                    && 10000 === $payload['timeout'];
            }))
            ->willReturn(['popupPageId' => 'popup999']);

        $popup = $this->context->waitForPopup($action, ['timeout' => 10000]);

        $this->assertInstanceOf(PageInterface::class, $popup);
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
        $this->context->waitForPopup($action);
    }

    public function testWaitForPopupInvalidPageId(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['popupPageId' => 123]);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('No popup was created within the timeout period');

        $action = function () {};
        $this->context->waitForPopup($action);
    }
}
