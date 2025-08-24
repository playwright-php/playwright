<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Support\RingBuffer;

#[CoversClass(RingBuffer::class)]
class RingBufferTest extends TestCase
{
    #[Test]
    public function itCanBeInstantiated(): void
    {
        $buffer = new RingBuffer();

        $this->assertInstanceOf(RingBuffer::class, $buffer);
        $this->assertTrue($buffer->isEmpty());
        $this->assertEquals(0, $buffer->count());
    }

    #[Test]
    public function itCanBeInstantiatedWithCustomSize(): void
    {
        $buffer = new RingBuffer(10);

        $this->assertEquals(10, $buffer->getMaxSize());
    }

    #[Test]
    public function itThrowsExceptionForInvalidSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('RingBuffer size must be at least 1');

        new RingBuffer(0);
    }

    #[Test]
    public function itAddsLinesToBuffer(): void
    {
        $buffer = new RingBuffer(3);

        $buffer->push('line 1');
        $buffer->push('line 2');

        $this->assertEquals(2, $buffer->count());
        $this->assertFalse($buffer->isEmpty());
        $this->assertEquals(['line 1', 'line 2'], $buffer->toArray());
    }

    #[Test]
    public function itTrimsNewlines(): void
    {
        $buffer = new RingBuffer(3);

        $buffer->push("line 1\n");
        $buffer->push("line 2\r\n");
        $buffer->push("line 3\r");

        $expected = ['line 1', 'line 2', 'line 3'];
        $this->assertEquals($expected, $buffer->toArray());
    }

    #[Test]
    public function itMaintainsMaxSize(): void
    {
        $buffer = new RingBuffer(3);

        $buffer->push('line 1');
        $buffer->push('line 2');
        $buffer->push('line 3');
        $buffer->push('line 4');
        $buffer->push('line 5');

        $this->assertEquals(3, $buffer->count());
        $this->assertEquals(['line 3', 'line 4', 'line 5'], $buffer->toArray());
    }

    #[Test]
    public function itConvertsToString(): void
    {
        $buffer = new RingBuffer(5);

        $buffer->push('line 1');
        $buffer->push('line 2');
        $buffer->push('line 3');

        $expected = "line 1\nline 2\nline 3";
        $this->assertEquals($expected, $buffer->toString());
    }

    #[Test]
    public function itConvertsToStringWithCustomSeparator(): void
    {
        $buffer = new RingBuffer(5);

        $buffer->push('line 1');
        $buffer->push('line 2');
        $buffer->push('line 3');

        $expected = 'line 1 | line 2 | line 3';
        $this->assertEquals($expected, $buffer->toString(' | '));
    }

    #[Test]
    public function itCanBeCleared(): void
    {
        $buffer = new RingBuffer(5);

        $buffer->push('line 1');
        $buffer->push('line 2');

        $this->assertEquals(2, $buffer->count());

        $buffer->clear();

        $this->assertEquals(0, $buffer->count());
        $this->assertTrue($buffer->isEmpty());
        $this->assertEquals([], $buffer->toArray());
    }

    #[Test]
    public function itHandlesEmptyBuffer(): void
    {
        $buffer = new RingBuffer();

        $this->assertEquals('', $buffer->toString());
        $this->assertEquals([], $buffer->toArray());
        $this->assertTrue($buffer->isEmpty());
    }

    #[Test]
    public function itWorksWithSingleItemBuffer(): void
    {
        $buffer = new RingBuffer(1);

        $buffer->push('first');
        $this->assertEquals(['first'], $buffer->toArray());

        $buffer->push('second');
        $this->assertEquals(['second'], $buffer->toArray());

        $buffer->push('third');
        $this->assertEquals(['third'], $buffer->toArray());
    }
}
