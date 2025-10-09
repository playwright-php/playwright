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
use Playwright\API\APIRequestContextInterface;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Page\Page;
use Playwright\Transport\TransportInterface;

#[CoversClass(Page::class)]
final class PageRequestTest extends TestCase
{
    public function testRequestIsCached(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);
        $api = $this->createMock(APIRequestContextInterface::class);

        $context->expects($this->once())
            ->method('request')
            ->willReturn($api);

        $page = new Page($transport, $context, 'page-1');

        $first = $page->request();
        $second = $page->request();

        $this->assertSame($api, $first);
        $this->assertSame($first, $second, 'Page::request should return cached instance');
    }
}
