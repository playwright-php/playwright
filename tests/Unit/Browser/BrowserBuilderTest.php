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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserBuilder;
use Playwright\Browser\BrowserType;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Tests\Mocks\TestLogger;
use Playwright\Transport\TransportInterface;

#[CoversClass(BrowserBuilder::class)]
final class BrowserBuilderTest extends TestCase
{
    private const WS_ENDPOINT = 'ws://127.0.0.1:9222/devtools';
    private const CDP_ENDPOINT = 'http://127.0.0.1:9222/';

    /**
     * @return iterable<string, array{string, BrowserType}>
     */
    public static function browserNameProvider(): iterable
    {
        yield 'chromium' => ['chromium', BrowserType::CHROMIUM];
        yield 'firefox' => ['firefox', BrowserType::FIREFOX];
        yield 'webkit' => ['webkit', BrowserType::WEBKIT];
    }

    #[DataProvider('browserNameProvider')]
    public function testLaunchCarriesTheBrowserNameToTheBrowser(string $name, BrowserType $expected): void
    {
        $this->assertSame($expected, $this->builder($name)->launch()->browserType());
    }

    #[DataProvider('browserNameProvider')]
    public function testConnectCarriesTheBrowserNameToTheBrowser(string $name, BrowserType $expected): void
    {
        $this->assertSame($expected, $this->builder($name)->connect(self::WS_ENDPOINT)->browserType());
    }

    #[DataProvider('browserNameProvider')]
    public function testConnectOverCdpCarriesTheBrowserNameToTheBrowser(string $name, BrowserType $expected): void
    {
        $this->assertSame($expected, $this->builder($name)->connectOverCDP(self::CDP_ENDPOINT)->browserType());
    }

    private function builder(string $browserType): BrowserBuilder
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn([
            'browserId' => 'b1',
            'defaultContextId' => 'ctx1',
            'version' => '1.0',
        ]);

        return new BrowserBuilder($browserType, $transport, new TestLogger(), new PlaywrightConfig());
    }
}
