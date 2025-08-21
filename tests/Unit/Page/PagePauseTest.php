<?php
declare(strict_types=1);

namespace PlaywrightPHP\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Browser\BrowserContextInterface;
use PlaywrightPHP\Page\Page;
use PlaywrightPHP\Transport\TransportInterface;

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
