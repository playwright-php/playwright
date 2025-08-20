<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Transport\JsonRpc\LspFraming;

#[CoversClass(LspFraming::class)]
final class LspFramingTest extends TestCase
{
    public function testEncodeMessage(): void
    {
        $content = '{"jsonrpc":"2.0","id":1,"method":"test"}';
        $encoded = LspFraming::encode($content);

        $expectedLength = strlen($content);
        $expected = "Content-Length: {$expectedLength}\r\n\r\n".$content;
        $this->assertEquals($expected, $encoded);
    }

    public function testDecodeCompleteMessage(): void
    {
        $content = '{"jsonrpc":"2.0","id":1,"method":"test"}';
        $contentLength = strlen($content);
        $buffer = "Content-Length: {$contentLength}\r\n\r\n".$content;

        $result = LspFraming::decode($buffer);

        $this->assertCount(1, $result['messages']);
        $this->assertEquals($content, $result['messages'][0]);
        $this->assertEquals('', $result['remainingBuffer']);
    }

    public function testDecodeIncompleteMessage(): void
    {
        $buffer = "Content-Length: 38\r\n\r\n{\"jsonrpc\":\"2.0\""; // Partial content

        $result = LspFraming::decode($buffer);

        $this->assertEmpty($result['messages']);
        $this->assertEquals($buffer, $result['remainingBuffer']);
    }

    public function testDecodeMultipleMessages(): void
    {
        $content1 = '{"id":1}';
        $content2 = '{"id":2}';

        $buffer = "Content-Length: 8\r\n\r\n".$content1.
                  "Content-Length: 8\r\n\r\n".$content2;

        $result = LspFraming::decode($buffer);

        $this->assertCount(2, $result['messages']);
        $this->assertEquals($content1, $result['messages'][0]);
        $this->assertEquals($content2, $result['messages'][1]);
        $this->assertEquals('', $result['remainingBuffer']);
    }

    public function testDecodePartialHeaders(): void
    {
        $buffer = 'Content-Len'; // Incomplete headers

        $result = LspFraming::decode($buffer);

        $this->assertEmpty($result['messages']);
        $this->assertEquals($buffer, $result['remainingBuffer']);
    }

    public function testHasCompleteMessage(): void
    {
        $completeBuffer = "Content-Length: 8\r\n\r\n{\"id\":1}";
        $incompleteBuffer = "Content-Length: 20\r\n\r\n{\"id\":1}"; // Content too short

        $this->assertTrue(LspFraming::hasCompleteMessage($completeBuffer));
        $this->assertFalse(LspFraming::hasCompleteMessage($incompleteBuffer));
    }

    public function testGetExpectedLength(): void
    {
        $buffer = "Content-Length: 10\r\n\r\nincomplete";
        $headerLength = 18; // "Content-Length: 10"
        $separatorLength = 4; // "\r\n\r\n"
        $contentLength = 10;
        $expected = $headerLength + $separatorLength + $contentLength;

        $this->assertEquals($expected, LspFraming::getExpectedLength($buffer));
    }

    public function testInvalidContentLength(): void
    {
        $buffer = "Invalid-Header: value\r\n\r\ncontent";

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing or invalid Content-Length header');

        LspFraming::decode($buffer);
    }

    public function testEmptyContent(): void
    {
        $buffer = "Content-Length: 0\r\n\r\n";

        $result = LspFraming::decode($buffer);

        $this->assertCount(1, $result['messages']);
        $this->assertEquals('', $result['messages'][0]);
        $this->assertEquals('', $result['remainingBuffer']);
    }

    public function testLargeContent(): void
    {
        $content = str_repeat('x', 10000);
        $encoded = LspFraming::encode($content);
        $result = LspFraming::decode($encoded);

        $this->assertCount(1, $result['messages']);
        $this->assertEquals($content, $result['messages'][0]);
        $this->assertEquals('', $result['remainingBuffer']);
    }
}
