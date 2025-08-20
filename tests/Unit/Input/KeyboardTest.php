<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Input\Keyboard;
use PlaywrightPHP\Transport\TransportInterface;

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
}
