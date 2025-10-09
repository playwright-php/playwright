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
use Playwright\Input\Keyboard;
use Playwright\Transport\TransportInterface;

#[CoversClass(Keyboard::class)]
final class KeyboardTest extends TestCase
{
    private TransportInterface $transport;
    private Keyboard $keyboard;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->keyboard = new Keyboard($this->transport, 'page1');
    }

    public function testPress(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'keyboard.press',
                'pageId' => 'page1',
                'key' => 'Enter',
                'options' => [],
            ])
            ->willReturn([]);

        $this->keyboard->press('Enter');
    }

    public function testPressWithOptions(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'keyboard.press',
                'pageId' => 'page1',
                'key' => 'A',
                'options' => ['delay' => 100],
            ])
            ->willReturn([]);

        $this->keyboard->press('A', ['delay' => 100]);
    }

    public function testType(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'keyboard.type',
                'pageId' => 'page1',
                'text' => 'Hello World',
                'options' => [],
            ])
            ->willReturn([]);

        $this->keyboard->type('Hello World');
    }

    public function testTypeWithDelay(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'keyboard.type',
                'pageId' => 'page1',
                'text' => 'slow typing',
                'options' => ['delay' => 200],
            ])
            ->willReturn([]);

        $this->keyboard->type('slow typing', ['delay' => 200]);
    }

    public function testInsertText(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'keyboard.insertText',
                'pageId' => 'page1',
                'text' => 'inserted text',
            ])
            ->willReturn([]);

        $this->keyboard->insertText('inserted text');
    }

    public function testDown(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'keyboard.down',
                'pageId' => 'page1',
                'key' => 'Shift',
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->keyboard->down('Shift');
    }

    public function testDownWithArrowKey(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'keyboard.down',
                'pageId' => 'page1',
                'key' => 'ArrowDown',
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->keyboard->down('ArrowDown');
    }

    public function testUp(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'keyboard.up',
                'pageId' => 'page1',
                'key' => 'Shift',
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->keyboard->up('Shift');
    }

    public function testUpWithControlKey(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'keyboard.up',
                'pageId' => 'page1',
                'key' => 'Control',
            ])
            ->willReturn([]);

        $this->transport
            ->expects($this->once())
            ->method('processEvents');

        $this->keyboard->up('Control');
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
                    $this->assertSame('keyboard.down', $payload['action']);
                    $this->assertSame('page1', $payload['pageId']);
                    $this->assertSame('A', $payload['key']);
                } elseif (2 === $callCount) {
                    $this->assertSame('keyboard.up', $payload['action']);
                    $this->assertSame('page1', $payload['pageId']);
                    $this->assertSame('A', $payload['key']);
                }

                return [];
            });

        $this->transport
            ->expects($this->exactly(2))
            ->method('processEvents');

        $this->keyboard->down('A');
        $this->keyboard->up('A');
    }
}
