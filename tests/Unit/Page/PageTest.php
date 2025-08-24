<?php

namespace PlaywrightPHP\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Browser\BrowserContextInterface;
use PlaywrightPHP\Input\KeyboardInterface;
use PlaywrightPHP\Input\MouseInterface;
use PlaywrightPHP\Page\Page;
use PlaywrightPHP\Page\PageEventHandlerInterface;
use PlaywrightPHP\Transport\TransportInterface;

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
