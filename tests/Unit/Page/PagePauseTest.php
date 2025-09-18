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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Page\Page;
use Playwright\Transport\TransportInterface;

#[CoversClass(Page::class)]
class PagePauseTest extends TestCase
{
    #[Test]
    public function itSendsPauseCommand(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) {
                return ($payload['action'] ?? null) === 'page.pause'
                    && ($payload['pageId'] ?? null) === 'page_1';
            }))
            ->willReturn(['success' => true]);

        $page = new Page($transport, $context, 'page_1');
        $page->pause();
        $this->assertTrue(true, 'pause() should dispatch page.pause');
    }
}
