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

namespace Playwright\Tests\Unit\Event;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Playwright\Event\EventEmitter;

#[CoversTrait(EventEmitter::class)]
final class EventEmitterTest extends TestCase
{
    private TestEventEmitter $emitter;

    protected function setUp(): void
    {
        $this->emitter = new TestEventEmitter();
    }

    public function testOnAddsListener(): void
    {
        $called = false;
        $this->emitter->on('test', function () use (&$called) {
            $called = true;
        });

        $this->emitter->triggerEvent('test');
        $this->assertTrue($called);
    }

    public function testMultipleListenersForSameEvent(): void
    {
        $calls = [];
        $this->emitter->on('test', function () use (&$calls) {
            $calls[] = 'first';
        });
        $this->emitter->on('test', function () use (&$calls) {
            $calls[] = 'second';
        });

        $this->emitter->triggerEvent('test');
        $this->assertEquals(['first', 'second'], $calls);
    }

    public function testOnceRemovesListenerAfterFirstCall(): void
    {
        $callCount = 0;
        $this->emitter->once('test', function () use (&$callCount) {
            ++$callCount;
        });

        $this->emitter->triggerEvent('test');
        $this->emitter->triggerEvent('test');
        $this->assertEquals(1, $callCount);
    }

    public function testListenerReceivesArguments(): void
    {
        $receivedArgs = null;
        $this->emitter->on('test', function (...$args) use (&$receivedArgs) {
            $receivedArgs = $args;
        });

        $this->emitter->triggerEvent('test', ['arg1', 'arg2', 123]);
        $this->assertEquals(['arg1', 'arg2', 123], $receivedArgs);
    }

    public function testRemoveListenerRemovesSpecificListener(): void
    {
        $firstCalled = false;
        $secondCalled = false;

        $firstListener = function () use (&$firstCalled) {
            $firstCalled = true;
        };
        $secondListener = function () use (&$secondCalled) {
            $secondCalled = true;
        };

        $this->emitter->on('test', $firstListener);
        $this->emitter->on('test', $secondListener);
        $this->emitter->removeListener('test', $firstListener);

        $this->emitter->triggerEvent('test');
        $this->assertFalse($firstCalled);
        $this->assertTrue($secondCalled);
    }

    public function testRemoveListenerForNonExistentEvent(): void
    {
        $listener = function () {
        };

        $this->emitter->removeListener('nonexistent', $listener);
        $this->assertTrue(true);
    }

    public function testRemoveNonExistentListener(): void
    {
        $listener1 = function () {
        };
        $listener2 = function () {
        };

        $this->emitter->on('test', $listener1);

        $this->emitter->removeListener('test', $listener2);
        $this->assertTrue(true);
    }

    public function testEmitWithNoListeners(): void
    {
        $this->emitter->triggerEvent('nonexistent');
        $this->assertTrue(true);
    }

    public function testOnceWithMultipleEvents(): void
    {
        $calls = [];
        $this->emitter->once('event1', function () use (&$calls) {
            $calls[] = 'event1';
        });
        $this->emitter->once('event2', function () use (&$calls) {
            $calls[] = 'event2';
        });

        $this->emitter->triggerEvent('event1');
        $this->emitter->triggerEvent('event2');
        $this->emitter->triggerEvent('event1');
        $this->emitter->triggerEvent('event2');

        $this->assertEquals(['event1', 'event2'], $calls);
    }
}

/**
 * Test class that uses EventEmitter trait for testing.
 */
final class TestEventEmitter
{
    use EventEmitter;

    public function triggerEvent(string $event, array $args = []): void
    {
        $this->emit($event, $args);
    }
}
