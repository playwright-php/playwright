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

namespace Playwright\Tests\Functional\Dialog;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Dialog\Dialog;
use Playwright\Dialog\DialogInterface;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Dialog::class)]
final class DialogTest extends FunctionalTestCase
{
    public function testCanHandleAlert(): void
    {
        $dialogHandled = false;
        $dialogMessage = '';

        $this->page->events()->onDialog(function ($dialog) use (&$dialogHandled, &$dialogMessage): void {
            $this->assertInstanceOf(DialogInterface::class, $dialog);
            $dialogMessage = $dialog->message();
            $dialog->accept();
            $dialogHandled = true;
        });

        $this->goto('/dialogs.html');

        $this->page->click('#trigger-alert');

        $this->page->waitForSelector('#alert-result');

        $this->assertTrue($dialogHandled);
        $this->assertSame('This is an alert message', $dialogMessage);

        $result = $this->page->locator('#alert-result')->textContent();
        $this->assertSame('Alert was shown', $result);
    }

    public function testCanAcceptConfirm(): void
    {
        $this->page->events()->onDialog(function ($dialog): void {
            $this->assertSame('confirm', $dialog->type());
            $this->assertSame('Do you confirm?', $dialog->message());
            $dialog->accept();
        });

        $this->goto('/dialogs.html');

        $this->page->click('#trigger-confirm');

        $this->page->waitForSelector('#confirm-result');

        $result = $this->page->locator('#confirm-result')->textContent();
        $this->assertSame('Confirmed', $result);
    }

    public function testCanDismissConfirm(): void
    {
        $this->page->events()->onDialog(function ($dialog): void {
            $this->assertSame('confirm', $dialog->type());
            $dialog->dismiss();
        });

        $this->goto('/dialogs.html');

        $this->page->click('#trigger-confirm');

        $this->page->waitForSelector('#confirm-result');

        $result = $this->page->locator('#confirm-result')->textContent();
        $this->assertSame('Cancelled', $result);
    }

    public function testCanHandlePromptWithInput(): void
    {
        $this->page->events()->onDialog(function ($dialog): void {
            $this->assertSame('prompt', $dialog->type());
            $this->assertSame('Enter your name:', $dialog->message());
            $this->assertSame('Default Name', $dialog->defaultValue());
            $dialog->accept('John Doe');
        });

        $this->goto('/dialogs.html');

        $this->page->click('#trigger-prompt');

        $this->page->waitForSelector('#prompt-result');

        $result = $this->page->locator('#prompt-result')->textContent();
        $this->assertSame('You entered: John Doe', $result);
    }

    public function testCanDismissPrompt(): void
    {
        $this->page->events()->onDialog(function ($dialog): void {
            $this->assertSame('prompt', $dialog->type());
            $dialog->dismiss();
        });

        $this->goto('/dialogs.html');

        $this->page->click('#trigger-prompt');

        $this->page->waitForSelector('#prompt-result');

        $result = $this->page->locator('#prompt-result')->textContent();
        $this->assertSame('Prompt cancelled', $result);
    }

    public function testDialogTypeProperty(): void
    {
        $dialogType = '';

        $this->page->events()->onDialog(function ($dialog) use (&$dialogType): void {
            $dialogType = $dialog->type();
            $dialog->accept();
        });

        $this->goto('/dialogs.html');

        $this->page->click('#trigger-alert');

        $this->page->waitForSelector('#alert-result');

        $this->assertSame('alert', $dialogType);
    }
}
