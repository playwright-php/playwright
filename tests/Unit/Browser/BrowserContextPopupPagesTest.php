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
use Playwright\Browser\BrowserContext;
use Playwright\Transport\TransportInterface;

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
