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
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Page\PageInterface;
use PlaywrightPHP\Transport\TransportInterface;

#[CoversClass(BrowserContext::class)]
final class BrowserContextPopupTest extends TestCase
{
    public function testWaitForPopupWithCallbackCoordinatedTransport(): void
    {
        $transport = new class implements TransportInterface {
            public bool $stored = false;
            public $storedCallback; // property type callable not allowed in all PHP versions
            public array $lastMessage = [];

            public function connect(): void
            {
            }

            public function disconnect(): void
            {
            }

            public function isConnected(): bool
            {
                return true;
            }

            public function processEvents(): void
            {
            }

            public function sendAsync(array $message): void
            {
            }

            // Extra method detected by method_exists in BrowserContext
            public function storePendingCallback(string $requestId, callable $callback): void
            {
                $this->stored = true;
                $this->storedCallback = $callback;
            }

            public function send(array $message): array
            {
                $this->lastMessage = $message;
                // Simulate server returning a popup page identifier
                if (($message['action'] ?? '') === 'context.waitForPopup') {
                    return ['popupPageId' => 'popup_ctx_123'];
                }

                return [];
            }
        };

        $context = new BrowserContext($transport, 'ctx_1', new PlaywrightConfig());

        $actionExecuted = false;
        $popup = $context->waitForPopup(function () use (&$actionExecuted): void {
            $actionExecuted = true; // should not run immediately in coordinated path
        });

        // Transport was asked to store callback
        $this->assertTrue($transport->stored, 'Expected storePendingCallback to be called');

        // Correct server action was sent
        $this->assertSame('context.waitForPopup', $transport->lastMessage['action'] ?? null);
        $this->assertSame('ctx_1', $transport->lastMessage['contextId'] ?? null);

        // The action should not have executed immediately here (it would be executed by transport when server requests it)
        $this->assertFalse($actionExecuted, 'Callback should be deferred in coordinated path');

        $this->assertInstanceOf(PageInterface::class, $popup);
    }

    public function testWaitForPopupFallbackWithoutCallbackSupport(): void
    {
        // A plain mock transport without storePendingCallback method triggers fallback execution of the action
        $transport = new class implements TransportInterface {
            public array $lastMessage = [];

            public function connect(): void
            {
            }

            public function disconnect(): void
            {
            }

            public function isConnected(): bool
            {
                return true;
            }

            public function processEvents(): void
            {
            }

            public function sendAsync(array $message): void
            {
            }

            public function send(array $message): array
            {
                $this->lastMessage = $message;
                if (($message['action'] ?? '') === 'context.waitForPopup') {
                    return ['popupPageId' => 'popup_ctx_456'];
                }

                return [];
            }
        };

        $context = new BrowserContext($transport, 'ctx_2', new PlaywrightConfig());

        $actionExecuted = false;
        $popup = $context->waitForPopup(function () use (&$actionExecuted): void {
            $actionExecuted = true; // should execute immediately in fallback path
        });

        // Fallback should have executed the action synchronously
        $this->assertTrue($actionExecuted, 'Callback should execute immediately in fallback path');

        // Correct server action was sent
        $this->assertSame('context.waitForPopup', $transport->lastMessage['action'] ?? null);
        $this->assertSame('ctx_2', $transport->lastMessage['contextId'] ?? null);

        $this->assertInstanceOf(PageInterface::class, $popup);
    }

    public function testWaitForPopupThrowsOnInvalidResponse(): void
    {
        $transport = new class implements TransportInterface {
            public function connect(): void
            {
            }

            public function disconnect(): void
            {
            }

            public function isConnected(): bool
            {
                return true;
            }

            public function processEvents(): void
            {
            }

            public function sendAsync(array $message): void
            {
            }

            public function send(array $message): array
            {
                // Return an invalid response (missing popupPageId)
                return ['popupPageId' => null];
            }
        };

        $context = new BrowserContext($transport, 'ctx_3', new PlaywrightConfig());

        $this->expectException(\PlaywrightPHP\Exception\TimeoutException::class);
        $this->expectExceptionMessage('No popup was created within the timeout period');

        $context->waitForPopup(static function (): void {});
    }
}
