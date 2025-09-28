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
use Playwright\Input\KeyboardInterface;
use Playwright\Input\MouseInterface;
use Playwright\Page\Page;
use Playwright\Page\PageEventHandlerInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(Page::class)]
class PageTest extends TestCase
{
    protected Page $page;

    protected function setUp(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);
        $pageId = 'page-id';

        $this->page = new Page($transport, $context, $pageId);
    }

    public function testGetKeyboard(): void
    {
        $keyboard = $this->page->keyboard();

        $this->assertInstanceOf(KeyboardInterface::class, $keyboard);
    }

    public function testGetMouse(): void
    {
        $mouse = $this->page->mouse();

        $this->assertInstanceOf(MouseInterface::class, $mouse);
    }

    public function testGetEvents(): void
    {
        $events = $this->page->events();

        $this->assertInstanceOf(PageEventHandlerInterface::class, $events);
    }
}
