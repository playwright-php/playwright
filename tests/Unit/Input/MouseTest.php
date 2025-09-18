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

namespace Playwright\Tests\Unit\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Input\Mouse;
use Playwright\Transport\TransportInterface;

#[CoversClass(Mouse::class)]
final class MouseTest extends TestCase
{
    private TransportInterface $transport;
    private Mouse $mouse;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->mouse = new Mouse($this->transport, 'page1');
    }

    public function testClick(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.click',
                'pageId' => 'page1',
                'x' => 100,
                'y' => 200,
                'options' => [],
            ])
            ->willReturn([]);

        $this->mouse->click(100, 200);
    }

    public function testClickWithOptions(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.click',
                'pageId' => 'page1',
                'x' => 150,
                'y' => 250,
                'options' => ['button' => 'right', 'clickCount' => 2],
            ])
            ->willReturn([]);

        $this->mouse->click(150, 250, ['button' => 'right', 'clickCount' => 2]);
    }

    public function testMove(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.move',
                'pageId' => 'page1',
                'x' => 50,
                'y' => 75,
                'options' => [],
            ])
            ->willReturn([]);

        $this->mouse->move(50, 75);
    }

    public function testMoveWithSteps(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.move',
                'pageId' => 'page1',
                'x' => 200,
                'y' => 300,
                'options' => ['steps' => 5],
            ])
            ->willReturn([]);

        $this->mouse->move(200, 300, ['steps' => 5]);
    }

    public function testWheel(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.wheel',
                'pageId' => 'page1',
                'deltaX' => 10,
                'deltaY' => -20,
            ])
            ->willReturn([]);

        $this->mouse->wheel(10, -20);
    }
}
