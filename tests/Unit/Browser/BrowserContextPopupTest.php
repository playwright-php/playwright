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

namespace Playwright\Tests\Unit\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContext;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Page\PageInterface;
use Playwright\Transport\MockTransport;
use Playwright\Transport\TraceableTransport;

#[CoversClass(BrowserContext::class)]
final class BrowserContextPopupTest extends TestCase
{
    public function testWaitForPopupWithCallbackCoordinatedTransport(): void
    {
        $transport = new MockTransport();
        $transport->connect();

        $lastMessage = null;
        $transport->queueResponse(function (array $message) use (&$lastMessage): array {
            $lastMessage = $message;

            return ['popupPageId' => 'popup_ctx_123'];
        });

        $context = new BrowserContext($transport, 'ctx_1', new PlaywrightConfig());

        $actionExecuted = false;
        $popup = $context->waitForPopup(function () use (&$actionExecuted): void {
            $actionExecuted = true; // should not run immediately in coordinated path
        });

        $stored = $transport->getStoredPendingCallbacks();
        $this->assertCount(1, $stored);

        $this->assertSame('context.waitForPopup', $lastMessage['action'] ?? null);
        $this->assertSame('ctx_1', $lastMessage['contextId'] ?? null);
        $this->assertArrayHasKey($lastMessage['requestId'] ?? '', $stored);

        // The action should not have executed immediately here (it would be executed by transport when server requests it)
        $this->assertFalse($actionExecuted, 'Callback should be deferred in coordinated path');

        $this->assertInstanceOf(PageInterface::class, $popup);
    }

    public function testWaitForPopupFallbackWithoutCallbackSupport(): void
    {
        $inner = new MockTransport();
        $inner->connect();
        $lastMessage = null;
        $inner->queueResponse(function (array $message) use (&$lastMessage): array {
            $lastMessage = $message;

            return ['popupPageId' => 'popup_ctx_456'];
        });

        $transport = new TraceableTransport($inner);
        $transport->connect();

        $context = new BrowserContext($transport, 'ctx_2', new PlaywrightConfig());

        $actionExecuted = false;
        $popup = $context->waitForPopup(function () use (&$actionExecuted): void {
            $actionExecuted = true; // should execute immediately in fallback path
        });

        // Fallback should have executed the action synchronously
        $this->assertTrue($actionExecuted, 'Callback should execute immediately in fallback path');

        $sendCalls = $transport->getSendCalls();
        $this->assertSame('context.waitForPopup', $sendCalls[0]['message']['action'] ?? null);
        $this->assertSame('ctx_2', $sendCalls[0]['message']['contextId'] ?? null);
        $this->assertSame($lastMessage, $sendCalls[0]['message']);

        $this->assertInstanceOf(PageInterface::class, $popup);
    }

    public function testWaitForPopupThrowsOnInvalidResponse(): void
    {
        $transport = new MockTransport();
        $transport->connect();
        $transport->queueResponse(static fn (): array => ['popupPageId' => null]);

        $context = new BrowserContext($transport, 'ctx_3', new PlaywrightConfig());

        $this->expectException(\Playwright\Exception\TimeoutException::class);
        $this->expectExceptionMessage('No popup was created within the timeout period');

        $context->waitForPopup(static function (): void {});
    }
}
