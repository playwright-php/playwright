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

namespace Playwright\Tests\Unit\Dialog;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Dialog\Dialog;
use Playwright\Page\PageInterface;

#[CoversClass(Dialog::class)]
final class DialogTest extends TestCase
{
    public function testType(): void
    {
        $page = $this->createMock(PageInterface::class);
        $dialog = new Dialog($page, 'dialog-1', 'alert', 'Hello', null);

        $this->assertSame('alert', $dialog->type());
    }

    public function testMessage(): void
    {
        $page = $this->createMock(PageInterface::class);
        $dialog = new Dialog($page, 'dialog-1', 'alert', 'Hello World', null);

        $this->assertSame('Hello World', $dialog->message());
    }

    public function testDefaultValue(): void
    {
        $page = $this->createMock(PageInterface::class);
        $dialog = new Dialog($page, 'dialog-1', 'prompt', 'Enter name', 'default-value');

        $this->assertSame('default-value', $dialog->defaultValue());
    }

    public function testDefaultValueNull(): void
    {
        $page = $this->createMock(PageInterface::class);
        $dialog = new Dialog($page, 'dialog-1', 'alert', 'Hello', null);

        $this->assertNull($dialog->defaultValue());
    }

    public function testAccept(): void
    {
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->once())
            ->method('handleDialog')
            ->with('dialog-1', true, null);

        $dialog = new Dialog($page, 'dialog-1', 'confirm', 'Are you sure?', null);
        $dialog->accept();
    }

    public function testAcceptWithPromptText(): void
    {
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->once())
            ->method('handleDialog')
            ->with('dialog-1', true, 'my-input');

        $dialog = new Dialog($page, 'dialog-1', 'prompt', 'Enter text', null);
        $dialog->accept('my-input');
    }

    public function testDismiss(): void
    {
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->once())
            ->method('handleDialog')
            ->with('dialog-1', false);

        $dialog = new Dialog($page, 'dialog-1', 'confirm', 'Are you sure?', null);
        $dialog->dismiss();
    }

    public function testPage(): void
    {
        $page = $this->createMock(PageInterface::class);
        $dialog = new Dialog($page, 'dialog-1', 'alert', 'Hello', null);

        $this->assertSame($page, $dialog->page());
    }
}
