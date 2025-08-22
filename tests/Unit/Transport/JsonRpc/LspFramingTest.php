<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Transport\JsonRpc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Transport\JsonRpc\LspFraming;

#[CoversClass(LspFraming::class)]
final class LspFramingTest extends TestCase
{
    public function testEncoding(): void
    {
        $json = '{"jsonrpc":"2.0","id":1}';
        $encoded = LspFraming::encode($json);
        $expected = "Content-Length: 24\r\n\r\n{\"jsonrpc\":\"2.0\",\"id\":1}";
        
        self::assertSame($expected, $encoded);
    }

    public function testEncodingWithDifferentSizes(): void
    {
        $testCases = [
            '{"test":1}' => "Content-Length: 10\r\n\r\n{\"test\":1}",
            '' => "Content-Length: 0\r\n\r\n",
            'hello' => "Content-Length: 5\r\n\r\nhello",
        ];

        foreach ($testCases as $input => $expected) {
            self::assertSame($expected, LspFraming::encode($input), "Failed for input: {$input}");
        }
    }

    public function testDecodingSingleCompleteMessage(): void
    {
        $framed = "Content-Length: 24\r\n\r\n{\"jsonrpc\":\"2.0\",\"id\":1}";
        $decoded = LspFraming::decode($framed);
        
        self::assertCount(1, $decoded['messages']);
        self::assertSame('{"jsonrpc":"2.0","id":1}', $decoded['messages'][0]);
        self::assertSame('', $decoded['remainingBuffer']);
    }

    public function testDecodingIncompleteMessage(): void
    {
        $testCases = [
            'Content-Length: 25', // Header only
            "Content-Length: 25\r\n", // Incomplete header separator
            "Content-Length: 25\r\n\r\n{\"jsonrpc\":", // Partial payload
            "Content-Length: 25\r\n\r\n{\"jsonrpc\":\"2.0\"", // Incomplete payload
        ];

        foreach ($testCases as $incomplete) {
            $decoded = LspFraming::decode($incomplete);
            self::assertEmpty($decoded['messages'], "Should have no messages for: {$incomplete}");
            self::assertSame($incomplete, $decoded['remainingBuffer'], "Buffer should be unchanged for: {$incomplete}");
        }
    }

    public function testDecodingMultipleMessages(): void
    {
        $message1 = '{"id":1}';
        $message2 = '{"id":2}';
        $framed1 = LspFraming::encode($message1);
        $framed2 = LspFraming::encode($message2);
        $combined = $framed1 . $framed2;
        
        $decoded = LspFraming::decode($combined);
        
        self::assertCount(2, $decoded['messages']);
        self::assertSame($message1, $decoded['messages'][0]);
        self::assertSame($message2, $decoded['messages'][1]);
        self::assertSame('', $decoded['remainingBuffer']);
    }

    public function testDecodingWithTrailingData(): void
    {
        $completeMessage = '{"id":1}';
        $framed = LspFraming::encode($completeMessage);
        $trailingData = 'Content-Length: 50';
        $input = $framed . $trailingData;
        
        $decoded = LspFraming::decode($input);
        
        self::assertCount(1, $decoded['messages']);
        self::assertSame($completeMessage, $decoded['messages'][0]);
        self::assertSame($trailingData, $decoded['remainingBuffer']);
    }

    public function testDecodingMultipleMessagesWithTrailingData(): void
    {
        $message1 = '{"id":1}';
        $message2 = '{"id":2}';
        $framed1 = LspFraming::encode($message1);
        $framed2 = LspFraming::encode($message2);
        $trailingData = 'Content-Length: 100\r\n\r\nincomplete';
        $input = $framed1 . $framed2 . $trailingData;
        
        $decoded = LspFraming::decode($input);
        
        self::assertCount(2, $decoded['messages']);
        self::assertSame($message1, $decoded['messages'][0]);
        self::assertSame($message2, $decoded['messages'][1]);
        self::assertSame($trailingData, $decoded['remainingBuffer']);
    }

    public function testHasCompleteMessage(): void
    {
        $completeMessage = LspFraming::encode('{"test":1}');
        self::assertTrue(LspFraming::hasCompleteMessage($completeMessage));

        $incompleteMessage = 'Content-Length: 10\r\n\r\n{"te';
        self::assertFalse(LspFraming::hasCompleteMessage($incompleteMessage));

        $headerOnly = 'Content-Length: 10';
        self::assertFalse(LspFraming::hasCompleteMessage($headerOnly));

        self::assertFalse(LspFraming::hasCompleteMessage(''));
    }

    public function testGetExpectedLength(): void
    {
        $headerComplete = "Content-Length: 25\r\n\r\n";
        self::assertSame(47, LspFraming::getExpectedLength($headerComplete)); // 22 (header) + 25 (content)

        $headerIncomplete = 'Content-Length: 25';
        self::assertNull(LspFraming::getExpectedLength($headerIncomplete));

        $noHeader = 'some random data';
        self::assertNull(LspFraming::getExpectedLength($noHeader));

        self::assertNull(LspFraming::getExpectedLength(''));
    }

    public function testInvalidContentLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid Content-Length header');
        
        LspFraming::decode("Invalid-Header: 25\r\n\r\ncontent");
    }

    public function testLargePayload(): void
    {
        $largeContent = str_repeat('x', 1000000); // 1MB
        $encoded = LspFraming::encode($largeContent);
        $decoded = LspFraming::decode($encoded);
        
        self::assertCount(1, $decoded['messages']);
        self::assertSame($largeContent, $decoded['messages'][0]);
        self::assertSame('', $decoded['remainingBuffer']);
    }

    public function testMultiByteCharacters(): void
    {
        $utf8Content = '{"text":"こんにちは世界🌍"}';
        $encoded = LspFraming::encode($utf8Content);
        $decoded = LspFraming::decode($encoded);
        
        self::assertCount(1, $decoded['messages']);
        self::assertSame($utf8Content, $decoded['messages'][0]);
        self::assertSame('', $decoded['remainingBuffer']);
    }

    public function testEdgeCaseHeaders(): void
    {
        $testCases = [
            "content-length: 5\r\n\r\nhello", // lowercase
            "Content-Length:5\r\n\r\nhello", // no space after colon
            "Content-Length: 5 \r\n\r\nhello", // trailing space
        ];

        foreach ($testCases as $input) {
            $decoded = LspFraming::decode($input);
            self::assertCount(1, $decoded['messages'], "Failed for: {$input}");
            self::assertSame('hello', $decoded['messages'][0]);
        }
    }
}