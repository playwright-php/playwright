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
use Playwright\Browser\Browser;
use Playwright\Browser\BrowserType;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Transport\TransportInterface;

#[CoversClass(Browser::class)]
final class BrowserTest extends TestCase
{
    public function testBrowserTypeReportsTheEngineItWasBuiltWith(): void
    {
        $browser = $this->browser(BrowserType::FIREFOX);

        $this->assertSame(BrowserType::FIREFOX, $browser->browserType());
    }

    public function testBrowserTypeDefaultsToChromium(): void
    {
        $browser = new Browser($this->transport(), 'b', 'ctx_default', '1.0', new PlaywrightConfig());

        $this->assertSame(BrowserType::CHROMIUM, $browser->browserType());
    }

    public function testBindSendsTheTitleAndReturnsTheEndpoint(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'bind',
                'browserId' => 'b',
                'title' => 'my-browser',
                'options' => ['port' => 0],
            ])
            ->willReturn(['endpoint' => 'ws://127.0.0.1:4242/abc']);

        $browser = new Browser($transport, 'b', 'ctx_default', '1.0', new PlaywrightConfig());

        $this->assertSame('ws://127.0.0.1:4242/abc', $browser->bind('my-browser', ['port' => 0]));
    }

    public function testBindThrowsWhenNoEndpointComesBack(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn(['success' => true]);

        $browser = new Browser($transport, 'b', 'ctx_default', '1.0', new PlaywrightConfig());

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid endpoint returned from transport');

        $browser->bind('my-browser');
    }

    public function testUnbindSendsTheBrowserId(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'unbind',
                'browserId' => 'b',
            ])
            ->willReturn([]);

        $browser = new Browser($transport, 'b', 'ctx_default', '1.0', new PlaywrightConfig());

        $browser->unbind();
    }

    private function browser(BrowserType $type): Browser
    {
        return new Browser($this->transport(), 'b', 'ctx_default', '1.0', new PlaywrightConfig(), $type);
    }

    private function transport(): TransportInterface
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn(['contextId' => 'ctx_new']);

        return $transport;
    }
}
