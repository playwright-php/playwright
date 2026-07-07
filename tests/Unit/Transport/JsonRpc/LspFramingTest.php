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

namespace Playwright\Tests\Unit\Transport\JsonRpc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Transport\JsonRpc\LspFraming;

#[CoversClass(LspFraming::class)]
final class LspFramingTest extends TestCase
{
    public function testDecodesASingleWellFormedMessage(): void
    {
        $decoded = LspFraming::decode(LspFraming::encode('{"ok":1}'));

        $this->assertSame(['{"ok":1}'], $decoded['messages']);
        $this->assertSame('', $decoded['remainingBuffer']);
    }

    public function testDecodesMultipleConsecutiveMessages(): void
    {
        $buffer = LspFraming::encode('{"ok":1}').LspFraming::encode('{"ok":2}');

        $decoded = LspFraming::decode($buffer);

        $this->assertSame(['{"ok":1}', '{"ok":2}'], $decoded['messages']);
    }

    public function testBuffersAnIncompleteMessageInsteadOfFailing(): void
    {
        $partial = substr(LspFraming::encode('{"ok":1}'), 0, -3);

        $decoded = LspFraming::decode($partial);

        $this->assertSame([], $decoded['messages']);
        $this->assertSame($partial, $decoded['remainingBuffer']);
    }

    public function testResyncsPastStrayNonLspOutputInsteadOfThrowing(): void
    {
        $frame1 = LspFraming::encode('{"ok":1}');
        $frame2 = LspFraming::encode('{"ok":2}');
        $polluted = "crash spew\nstack line\n".$frame1.'garbage'.$frame2;

        $decoded = LspFraming::decode($polluted);

        $this->assertSame(['{"ok":1}', '{"ok":2}'], $decoded['messages']);
    }

    public function testBuffersPureGarbageWithNoContentLengthMarkerAtAll(): void
    {
        $decoded = LspFraming::decode("this is not a frame at all\r\n\r\n");

        $this->assertSame([], $decoded['messages']);
    }

    public function testMalformedContentLengthValueIsSkippedRatherThanThrown(): void
    {
        $frame = LspFraming::encode('{"ok":1}');
        $polluted = "Content-Length: not-a-number\r\n\r\n".$frame;

        $decoded = LspFraming::decode($polluted);

        $this->assertSame(['{"ok":1}'], $decoded['messages']);
    }
}
