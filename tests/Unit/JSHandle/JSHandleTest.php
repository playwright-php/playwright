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

namespace Playwright\Tests\Unit\JSHandle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\JSHandle\JSHandle;
use Playwright\JSHandle\JSHandleInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(JSHandle::class)]
final class JSHandleTest extends TestCase
{
    public function testAsElementWrapsTheReturnedHandleId(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'jsHandle.asElement',
                'handleId' => 'handle-1',
            ])
            ->willReturn(['handleId' => 'handle-2']);

        $element = (new JSHandle($transport, 'handle-1'))->asElement();

        $this->assertInstanceOf(JSHandleInterface::class, $element);
    }

    public function testAsElementYieldsNullForANonElementHandle(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn(['handleId' => null]);

        $this->assertNull((new JSHandle($transport, 'handle-1'))->asElement());
    }
}
