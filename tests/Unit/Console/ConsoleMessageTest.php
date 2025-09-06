<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Console\ConsoleMessage;

#[CoversClass(ConsoleMessage::class)]
class ConsoleMessageTest extends TestCase
{
    public function testType(): void
    {
        $message = new ConsoleMessage([
            'type' => 'log',
            'text' => 'Hello, world!',
            'args' => [],
            'location' => [
                'url' => 'http://example.com',
                'lineNumber' => 10,
                'columnNumber' => 5,
            ],
        ]);

        $this->assertSame('log', $message->type());
    }

    public function testText(): void
    {
        $message = new ConsoleMessage([
            'type' => 'log',
            'text' => 'Hello, world!',
            'args' => [],
            'location' => [
                'url' => 'http://example.com',
                'lineNumber' => 10,
                'columnNumber' => 5,
            ],
        ]);

        $this->assertSame('Hello, world!', $message->text());
    }

    public function testArgs(): void
    {
        $message = new ConsoleMessage([
            'type' => 'log',
            'text' => 'Hello, world!',
            'args' => [1, 2, 3],
            'location' => [
                'url' => 'http://example.com',
                'lineNumber' => 10,
                'columnNumber' => 5,
            ],
        ]);

        $this->assertSame([1, 2, 3], $message->args());
    }

    public function testLocation(): void
    {
        $location = [
            'url' => 'http://example.com',
            'lineNumber' => 10,
            'columnNumber' => 5,
        ];

        $message = new ConsoleMessage([
            'type' => 'log',
            'text' => 'Hello, world!',
            'args' => [],
            'location' => $location,
        ]);

        $this->assertSame($location, $message->location());
    }

    public function testInvalidType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid console message type');

        $message = new ConsoleMessage([
            'type' => 123,
            'text' => 'Hello, world!',
            'args' => [],
            'location' => [
                'url' => 'http://example.com',
                'lineNumber' => 10,
                'columnNumber' => 5,
            ],
        ]);

        $message->type();
    }

    public function testInvalidText(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid console message text');

        $message = new ConsoleMessage([
            'type' => 'log',
            'text' => 123,
            'args' => [],
            'location' => [
                'url' => 'http://example.com',
                'lineNumber' => 10,
                'columnNumber' => 5,
            ],
        ]);

        $message->text();
    }

    public function testInvalidArgs(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid console message args');

        $message = new ConsoleMessage([
            'type' => 'log',
            'text' => 'Hello, world!',
            'args' => 'not an array',
            'location' => [
                'url' => 'http://example.com',
                'lineNumber' => 10,
                'columnNumber' => 5,
            ],
        ]);

        $message->args();
    }
}
