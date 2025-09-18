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
use Playwright\Network\NetworkThrottling;
use Playwright\Page\PageInterface;
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
            ]);

        $this->context->clearCookies();
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
}
