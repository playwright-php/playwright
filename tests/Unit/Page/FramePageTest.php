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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Page\Page;
use Playwright\Page\PageInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(Page::class)]
class FramePageTest extends TestCase
{
    private MockObject|TransportInterface $transport;
    private MockObject|BrowserContextInterface $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transport = $this->createMock(TransportInterface::class);
        $this->context = $this->createMock(BrowserContextInterface::class);
    }

    private function createPage(): PageInterface
    {
        return new Page($this->transport, $this->context, 'page-1');
    }

    public function testMainFrame(): void
    {
        $page = $this->createPage();
        $frame = $page->mainFrame();
        $this->assertSame('Frame(selector=":root")', (string) $frame);
    }

    public function testFrames(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(fn (array $payload) => 'page.frames' === $payload['action'] && 'page-1' === $payload['pageId']))
            ->willReturn(['frames' => [
                ['selector' => 'iframe#one'],
                ['selector' => 'iframe[name="two"]'],
            ]]);

        $page = $this->createPage();
        $frames = $page->frames();
        $this->assertCount(2, $frames);
        $this->assertSame('Frame(selector="iframe#one")', (string) $frames[0]);
    }

    public function testFrameFind(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) {
                return 'page.frame' === $payload['action']
                    && 'page-1' === $payload['pageId']
                    && isset($payload['options']['name'])
                    && 'foo' === $payload['options']['name'];
            }))
            ->willReturn(['selector' => 'iframe#foo']);

        $page = $this->createPage();
        $frame = $page->frame(['name' => 'foo']);
        $this->assertNotNull($frame);
        $this->assertSame('Frame(selector="iframe#foo")', (string) $frame);
    }
}
