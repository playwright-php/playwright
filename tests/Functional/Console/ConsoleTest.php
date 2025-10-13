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

namespace Playwright\Tests\Functional\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Console\ConsoleMessage;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(ConsoleMessage::class)]
final class ConsoleTest extends FunctionalTestCase
{
    public function testCanCaptureLogMessage(): void
    {
        $messages = [];

        $this->page->events()->onConsole(function ($message) use (&$messages): void {
            $messages[] = [
                'type' => $message->type(),
                'text' => $message->text(),
            ];
        });

        $this->goto('/console.html');

        $this->page->click('#log-message');
        $this->page->waitForSelector('#output');

        $logMessages = array_filter($messages, fn ($m) => 'log' === $m['type']);
        self::assertNotEmpty($logMessages, 'Should have captured at least one log message');

        $hasExpectedLog = false;
        foreach ($logMessages as $msg) {
            if (str_contains($msg['text'], 'This is a log message')) {
                $hasExpectedLog = true;
                break;
            }
        }

        self::assertTrue($hasExpectedLog, 'Should have captured the expected log message');
    }

    public function testCanCaptureWarningMessage(): void
    {
        $warnings = [];

        $this->page->events()->onConsole(function ($message) use (&$warnings): void {
            if ('warning' === $message->type()) {
                $warnings[] = $message->text();
            }
        });

        $this->goto('/console.html');

        $this->page->click('#warn-message');
        $this->page->waitForSelector('#output');

        self::assertNotEmpty($warnings, 'Should have captured warning messages');
        self::assertStringContainsString('This is a warning message', $warnings[0]);
    }

    public function testCanCaptureErrorMessage(): void
    {
        $errors = [];

        $this->page->events()->onConsole(function ($message) use (&$errors): void {
            if ('error' === $message->type()) {
                $errors[] = $message->text();
            }
        });

        $this->goto('/console.html');

        $this->page->click('#error-message');
        $this->page->waitForSelector('#output');

        self::assertNotEmpty($errors, 'Should have captured error messages');
        self::assertStringContainsString('This is an error message', $errors[0]);
    }

    public function testCanCaptureInfoMessage(): void
    {
        $infoMessages = [];

        $this->page->events()->onConsole(function ($message) use (&$infoMessages): void {
            if ('info' === $message->type()) {
                $infoMessages[] = $message->text();
            }
        });

        $this->goto('/console.html');

        $this->page->click('#info-message');
        $this->page->waitForSelector('#output');

        self::assertNotEmpty($infoMessages, 'Should have captured info messages');
        self::assertStringContainsString('This is an info message', $infoMessages[0]);
    }

    public function testCanCaptureMultipleMessages(): void
    {
        $messages = [];

        $this->page->events()->onConsole(function ($message) use (&$messages): void {
            $messages[] = [
                'type' => $message->type(),
                'text' => $message->text(),
            ];
        });

        $this->goto('/console.html');

        $this->page->click('#multiple-logs');
        $this->page->waitForSelector('#output');

        $types = array_column($messages, 'type');

        self::assertContains('log', $types, 'Should have captured log message');
        self::assertContains('warning', $types, 'Should have captured warning message');
        self::assertContains('error', $types, 'Should have captured error message');
    }

    public function testConsoleMessageHasCorrectType(): void
    {
        $messageType = null;

        $this->page->events()->onConsole(function ($message) use (&$messageType): void {
            if (str_contains($message->text(), 'This is a log message')) {
                $messageType = $message->type();
            }
        });

        $this->goto('/console.html');

        $this->page->click('#log-message');
        $this->page->waitForSelector('#output');

        self::assertSame('log', $messageType, 'Message type should be "log"');
    }

    public function testCanCapturePageLoadLog(): void
    {
        $pageLoadMessages = [];

        $this->page->events()->onConsole(function ($message) use (&$pageLoadMessages): void {
            if (str_contains($message->text(), 'Page loaded')) {
                $pageLoadMessages[] = $message->text();
            }
        });

        $this->goto('/console.html');

        self::assertNotEmpty($pageLoadMessages, 'Should have captured page load message');
        self::assertStringContainsString('Page loaded', $pageLoadMessages[0]);
    }
}
