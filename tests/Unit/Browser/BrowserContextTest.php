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
use Playwright\Browser\StorageState;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Network\NetworkThrottling;
use Playwright\Page\PageInterface;
use Playwright\Tracing\TracingInterface;
use Playwright\Transport\MockTransport;
use Playwright\Transport\TraceableTransport;
use Playwright\Transport\TransportInterface;

#[CoversClass(BrowserContext::class)]
final class BrowserContextTest extends TestCase
{
    private TransportInterface $mockTransport;
    private BrowserContext $context;
    private PlaywrightConfig $config;

    protected function setUp(): void
    {
        $this->mockTransport = $this->createMock(TransportInterface::class);
        $this->config = new PlaywrightConfig();
        $this->context = new BrowserContext($this->mockTransport, 'context_1', $this->config);
    }

    public function testNewPageSendsCorrectCommand(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.newPage',
                'contextId' => 'context_1',
                'options' => ['viewport' => ['width' => 1280, 'height' => 720]],
            ])
            ->willReturn(['pageId' => 'page_1']);

        $page = $this->context->newPage(['viewport' => ['width' => 1280, 'height' => 720]]);

        $this->assertInstanceOf(PageInterface::class, $page);
    }

    public function testNewPageThrowsOnTransportError(): void
    {
        $this->mockTransport
            ->method('send')
            ->willReturn(['error' => 'Failed to create page']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Transport error in newPage: Failed to create page');

        $this->context->newPage();
    }

    public function testNewPageThrowsOnInvalidPageId(): void
    {
        $this->mockTransport
            ->method('send')
            ->willReturn(['success' => true]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No valid pageId returned from transport in newPage');

        $this->context->newPage();
    }

    public function testClipboardText(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.clipboardText',
                'contextId' => 'context_1',
            ])
            ->willReturn(['value' => 'clipboard content']);

        $result = $this->context->clipboardText();
        $this->assertEquals('clipboard content', $result);
    }

    public function testClose(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.close',
                'contextId' => 'context_1',
            ]);

        $this->context->close();
    }

    public function testAddCookies(): void
    {
        $cookies = [
            ['name' => 'session', 'value' => 'abc123', 'domain' => 'example.com'],
        ];

        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.addCookies',
                'contextId' => 'context_1',
                'cookies' => $cookies,
            ]);

        $this->context->addCookies($cookies);
    }

    public function testAddInitScript(): void
    {
        $script = 'window.testInit = true;';

        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.addInitScript',
                'contextId' => 'context_1',
                'script' => $script,
            ]);

        $this->context->addInitScript($script);
    }

    public function testClearCookies(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.clearCookies',
                'contextId' => 'context_1',
                'options' => [],
            ]);

        $this->context->clearCookies();
    }

    public function testClearCookiesWithOptions(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.clearCookies',
                'contextId' => 'context_1',
                'options' => ['name' => 'name_1'],
            ]);

        $this->context->clearCookies(['name' => 'name_1']);
    }

    public function testClearPermissions(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.clearPermissions',
                'contextId' => 'context_1',
            ]);

        $this->context->clearPermissions();
    }

    public function testCookies(): void
    {
        $expectedCookies = [
            ['name' => 'session', 'value' => 'abc123'],
        ];

        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.cookies',
                'contextId' => 'context_1',
                'urls' => ['https://example.com'],
            ])
            ->willReturn(['cookies' => $expectedCookies]);

        $result = $this->context->cookies(['https://example.com']);
        $this->assertEquals($expectedCookies, $result);
    }

    public function testCookiesWithoutUrls(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.cookies',
                'contextId' => 'context_1',
                'urls' => null,
            ])
            ->willReturn(['cookies' => []]);

        $result = $this->context->cookies();
        $this->assertEquals([], $result);
    }

    public function testExposeBinding(): void
    {
        $callback = function ($source, $data) {
            return 'processed: '.$data;
        };

        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.exposeBinding',
                'contextId' => 'context_1',
                'name' => 'testBinding',
            ]);

        $this->context->exposeBinding('testBinding', $callback);
    }

    public function testExposeFunction(): void
    {
        $callback = function ($arg1, $arg2) {
            return $arg1 + $arg2;
        };

        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.exposeFunction',
                'contextId' => 'context_1',
                'name' => 'addNumbers',
            ]);

        $this->context->exposeFunction('addNumbers', $callback);
    }

    public function testGrantPermissions(): void
    {
        $permissions = ['camera', 'microphone'];

        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.grantPermissions',
                'contextId' => 'context_1',
                'permissions' => $permissions,
            ]);

        $this->context->grantPermissions($permissions);
    }

    public function testPagesReturnsEmptyArrayInitially(): void
    {
        $pages = $this->context->pages();
        $this->assertEquals([], $pages);
    }

    public function testStorageState(): void
    {
        $expectedState = [
            'cookies' => [['name' => 'session', 'value' => 'abc']],
            'origins' => [],
        ];

        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.storageState',
                'contextId' => 'context_1',
                'options' => ['path' => '/tmp/state.json'],
            ])
            ->willReturn(['storageState' => $expectedState]);

        $result = $this->context->storageState('/tmp/state.json');
        $this->assertEquals($expectedState, $result);
    }

    public function testStorageStateWithoutPath(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.storageState',
                'contextId' => 'context_1',
                'options' => [],
            ])
            ->willReturn(['storageState' => []]);

        $result = $this->context->storageState();
        $this->assertEquals([], $result);
    }

    public function testSetStorageState(): void
    {
        $state = StorageState::fromArray([
            'cookies' => [[
                'name' => 'session',
                'value' => 'abc',
                'domain' => 'example.com',
                'path' => '/',
                'expires' => -1,
                'httpOnly' => false,
                'secure' => false,
                'sameSite' => 'Lax',
            ]],
            'origins' => [],
        ]);

        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.setStorageState',
                'contextId' => 'context_1',
                'storageState' => $state->toArray(),
            ]);

        $this->context->setStorageState($state);
    }

    public function testSetGeolocation(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.setGeolocation',
                'contextId' => 'context_1',
                'geolocation' => [
                    'latitude' => 37.7749,
                    'longitude' => -122.4194,
                    'accuracy' => 10.0,
                ],
            ]);

        $this->context->setGeolocation(37.7749, -122.4194, 10.0);
    }

    public function testSetOffline(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.setOffline',
                'contextId' => 'context_1',
                'offline' => true,
            ]);

        $this->context->setOffline(true);
    }

    public function testRoute(): void
    {
        $handler = function ($route) {
            $route->fulfill(['body' => 'mocked']);
        };

        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.route',
                'contextId' => 'context_1',
                'url' => '**/*.api',
            ]);

        $this->context->route('**/*.api', $handler);
    }

    public function testUnroute(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.unroute',
                'contextId' => 'context_1',
                'url' => '**/*.api',
            ]);

        $this->context->unroute('**/*.api');
    }

    public function testTracingReturnsTheSameInstance(): void
    {
        $tracing = $this->context->tracing();

        $this->assertInstanceOf(TracingInterface::class, $tracing);
        $this->assertSame($tracing, $this->context->tracing());
    }

    public function testGetEnv(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'getEnv',
                'name' => 'NODE_ENV',
            ])
            ->willReturn(['value' => 'test']);

        $result = $this->context->getEnv('NODE_ENV');
        $this->assertEquals('test', $result);
    }

    public function testGetEnvReturnsNullWhenNotSet(): void
    {
        $this->mockTransport
            ->method('send')
            ->willReturn([]);

        $result = $this->context->getEnv('NONEXISTENT');
        $this->assertNull($result);
    }

    public function testSetNetworkThrottling(): void
    {
        $throttling = NetworkThrottling::slow3G();

        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.setNetworkThrottling',
                'contextId' => 'context_1',
                'throttling' => $throttling->toArray(),
            ]);

        $this->context->setNetworkThrottling($throttling);
    }

    public function testDisableNetworkThrottling(): void
    {
        $this->mockTransport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'context.setNetworkThrottling',
                'contextId' => 'context_1',
                'throttling' => NetworkThrottling::none()->toArray(),
            ]);

        $this->context->disableNetworkThrottling();
    }

    /**
     * @param list<array<string, mixed>> $sent
     */
    private function createRecordingTransport(array &$sent): TransportInterface
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturnCallback(static function (array $payload) use (&$sent): array {
            $sent[] = $payload;

            return [];
        });

        return $transport;
    }

    public function testAutoTracingStartsWhenConfigEnablesTracing(): void
    {
        $sent = [];
        $transport = $this->createRecordingTransport($sent);

        new BrowserContext($transport, 'ctx_trace', new PlaywrightConfig(
            tracingEnabled: true,
            traceScreenshots: true,
            traceSnapshots: true,
        ));

        $this->assertSame([[
            'action' => 'context.startTracing',
            'contextId' => 'ctx_trace',
            'options' => ['screenshots' => true, 'snapshots' => true],
        ]], $sent);
    }

    public function testCloseSavesTheAutoTrace(): void
    {
        $traceDir = sys_get_temp_dir().'/pw-php-auto-trace-'.uniqid();
        $sent = [];
        $transport = $this->createRecordingTransport($sent);

        $context = new BrowserContext($transport, 'ctx_trace', new PlaywrightConfig(
            tracingEnabled: true,
            traceDir: $traceDir,
        ));

        try {
            $context->close();

            $actions = array_column($sent, 'action');
            $this->assertSame(['context.startTracing', 'context.stopTracing', 'context.close'], $actions);
            $this->assertSame($traceDir.'/trace-ctx_trace.zip', $sent[1]['path']);
        } finally {
            if (is_dir($traceDir)) {
                rmdir($traceDir);
            }
        }
    }

    public function testCloseThrowsWhenTheTraceDirectoryCannotBeCreated(): void
    {
        // A regular file as the parent makes the recursive mkdir fail whatever the permissions are.
        $blocker = (string) tempnam(sys_get_temp_dir(), 'pw-php-auto-trace-blocker');
        $traceDir = $blocker.'/traces';

        $sent = [];
        $transport = $this->createRecordingTransport($sent);

        $context = new BrowserContext($transport, 'ctx_trace', new PlaywrightConfig(
            tracingEnabled: true,
            traceDir: $traceDir,
        ));

        $thrown = null;

        try {
            $context->close();
        } catch (\RuntimeException $e) {
            $thrown = $e;
        } finally {
            unlink($blocker);
        }

        $this->assertInstanceOf(\RuntimeException::class, $thrown);
        $this->assertSame(sprintf('Trace directory "%s" was not created', $traceDir), $thrown->getMessage());
        $this->assertSame(['context.startTracing'], array_column($sent, 'action'));
    }

    public function testStartTracingIsNoOpWhenAutoTracingIsActive(): void
    {
        $sent = [];
        $transport = $this->createRecordingTransport($sent);

        $context = new BrowserContext($transport, 'ctx_trace', new PlaywrightConfig(tracingEnabled: true));

        $page = $this->createMock(PageInterface::class);
        $context->startTracing($page, ['screenshots' => true, 'snapshots' => true]);

        $this->assertCount(1, $sent);
        $this->assertSame('context.startTracing', $sent[0]['action']);
    }

    public function testManualStopTracingConsumesTheAutoTrace(): void
    {
        $sent = [];
        $transport = $this->createRecordingTransport($sent);

        $context = new BrowserContext($transport, 'ctx_trace', new PlaywrightConfig(tracingEnabled: true));

        $page = $this->createMock(PageInterface::class);
        $page->method('getPageIdForTransport')->willReturn('page_1');

        $context->stopTracing($page, '/tmp/manual-trace.zip');
        $context->close();

        $actions = array_column($sent, 'action');
        $this->assertSame(['context.startTracing', 'context.stopTracing', 'context.close'], $actions);
        $this->assertSame('/tmp/manual-trace.zip', $sent[1]['path']);
    }

    public function testDeleteCookieSendsAddCookiesWithExpiry(): void
    {
        $transport = $this->createMock(TransportInterface::class);

        $calledAdd = false;
        $that = $this;
        $transport->method('send')->willReturnCallback(function (array $payload) use (&$calledAdd, $that) {
            if (($payload['action'] ?? null) === 'context.cookies') {
                return [
                    'cookies' => [
                        [
                            'name' => 'foo',
                            'value' => '123',
                            'domain' => 'example.com',
                            'path' => '/',
                            'expires' => time() + 3600,
                            'httpOnly' => false,
                            'secure' => false,
                            'sameSite' => 'Lax',
                        ],
                        [
                            'name' => 'bar',
                            'value' => 'x',
                            'domain' => 'example.com',
                            'path' => '/',
                            'expires' => time() + 3600,
                            'httpOnly' => false,
                            'secure' => false,
                            'sameSite' => 'Lax',
                        ],
                    ],
                ];
            }

            if (($payload['action'] ?? null) === 'context.addCookies') {
                $that->assertArrayHasKey('cookies', $payload);
                $that->assertIsArray($payload['cookies']);
                $that->assertSame('foo', $payload['cookies'][0]['name']);
                $that->assertSame('example.com', $payload['cookies'][0]['domain']);
                $that->assertSame('/', $payload['cookies'][0]['path']);
                $that->assertSame(0, $payload['cookies'][0]['expires']);
                $calledAdd = true;

                return [];
            }

            return [];
        });

        $context = new BrowserContext($transport, 'ctx', new PlaywrightConfig());
        $context->deleteCookie('foo');

        $this->assertTrue($calledAdd, 'context.addCookies should be called to expire matching cookies');
    }

    public function testTracksPopupPagesViaEvents(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = new BrowserContext($transport, 'ctx1', new PlaywrightConfig());

        $this->assertCount(0, $context->pages());

        // Simulate server emitting a popup event with a pageId
        $context->dispatchEvent('popup', ['pageId' => 'p-123']);
        $this->assertCount(1, $context->pages());

        // Simulate server notifying page close
        $context->dispatchEvent('pageClosed', ['pageId' => 'p-123']);
        $this->assertCount(0, $context->pages());
    }

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
