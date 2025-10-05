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

    public function testDblclick(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.dblclick',
                'pageId' => 'page1',
                'x' => 100,
                'y' => 200,
                'options' => [],
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->mouse->dblclick(100, 200);
    }

    public function testDblclickWithOptions(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.dblclick',
                'pageId' => 'page1',
                'x' => 150,
                'y' => 250,
                'options' => ['button' => 'right', 'delay' => 100],
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->mouse->dblclick(150, 250, ['button' => 'right', 'delay' => 100]);
    }

    public function testDown(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.down',
                'pageId' => 'page1',
                'options' => [],
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->mouse->down();
    }

    public function testDownWithButton(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.down',
                'pageId' => 'page1',
                'options' => ['button' => 'right'],
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->mouse->down(['button' => 'right']);
    }

    public function testDownWithClickCount(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.down',
                'pageId' => 'page1',
                'options' => ['button' => 'left', 'clickCount' => 2],
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->mouse->down(['button' => 'left', 'clickCount' => 2]);
    }

    public function testUp(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.up',
                'pageId' => 'page1',
                'options' => [],
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->mouse->up();
    }

    public function testUpWithButton(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'mouse.up',
                'pageId' => 'page1',
                'options' => ['button' => 'middle'],
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->mouse->up(['button' => 'middle']);
    }

    public function testDownAndUpSequence(): void
    {
        $this->transport
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function (array $payload) {
                static $callCount = 0;
                ++$callCount;

                if (1 === $callCount) {
                    $this->assertSame('mouse.down', $payload['action']);
                    $this->assertSame('page1', $payload['pageId']);
                    $this->assertSame([], $payload['options']);
                } elseif (2 === $callCount) {
                    $this->assertSame('mouse.up', $payload['action']);
                    $this->assertSame('page1', $payload['pageId']);
                    $this->assertSame([], $payload['options']);
                }

                return [];
            });

        $this->transport
            ->expects($this->exactly(2))
            ->method('processEvents');

        $this->mouse->down();
        $this->mouse->up();
    }
}
