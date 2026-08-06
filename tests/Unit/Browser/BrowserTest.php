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
