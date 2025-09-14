<?php

declare(strict_types=1);

namespace PlaywrightPHP\Tests\Unit\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Browser\BrowserContext;
use PlaywrightPHP\Transport\TransportInterface;

#[CoversClass(BrowserContext::class)]
final class BrowserContextPopupPagesTest extends TestCase
{
    public function testTracksPopupPagesViaEvents(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = new BrowserContext($transport, 'ctx1');

        $this->assertCount(0, $context->pages());

        // Simulate server emitting a popup event with a pageId
        $context->dispatchEvent('popup', ['pageId' => 'p-123']);
        $this->assertCount(1, $context->pages());

        // Simulate server notifying page close
        $context->dispatchEvent('pageClosed', ['pageId' => 'p-123']);
        $this->assertCount(0, $context->pages());
    }
}

