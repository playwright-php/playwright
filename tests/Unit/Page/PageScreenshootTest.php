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

namespace Playwright\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Page\Page;
use Playwright\Transport\TransportInterface;

#[CoversClass(Page::class)]
final class PageScreenshootTest extends TestCase
{
    public function testScreenshotSendsCommandAndReturnsPath(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $expectedPath = sys_get_temp_dir().'/playwright-screenshot-unit-test.png';

        $matcher = $this->exactly(2);

        $transport->expects($matcher)
            ->method('send')
            ->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    self::assertEquals('page.url', $parameters[0]['action']);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    self::assertEquals('page.screenshot', $parameters[0]['action']);
                }
            })
            ->willReturnOnConsecutiveCalls(['value' => 'url'], ['binary' => 'test']);

        $page = new Page($transport, $context, 'page-id', new PlaywrightConfig());

        $result = $page->screenshot($expectedPath, ['fullPage' => true]);

        $this->assertSame($expectedPath, $result);

        unlink($expectedPath);
    }
}
