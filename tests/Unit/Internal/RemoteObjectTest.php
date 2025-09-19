<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Exception\RuntimeException;
use PlaywrightPHP\Internal\RemoteObject;
use PlaywrightPHP\Transport\TransportInterface;

#[CoversClass(RemoteObject::class)]
class RemoteObjectTest extends TestCase
{
    private TransportInterface $transport;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
    }

    #[Test]
    public function itCanBeCreated(): void
    {
        $object = $this->createRemoteObject('test-id', 'test-type');

        $this->assertEquals('test-id', $object->getRemoteId());
        $this->assertEquals('test-type', $object->getRemoteType());
        $this->assertFalse($object->isDisposed());
        $this->assertNull($object->getParent());
        $this->assertEmpty($object->getChildren());
    }

    #[Test]
    public function itCanAddAndRemoveChildren(): void
    {
        $parent = $this->createRemoteObject('parent-id', 'parent');
        $child = $this->createRemoteObject('child-id', 'child');

        $parent->addChild($child);

        $this->assertSame($parent, $child->getParent());
        $this->assertContains($child, $parent->getChildren());

        $parent->removeChild($child);

        $this->assertNull($child->getParent());
        $this->assertNotContains($child, $parent->getChildren());
    }

    #[Test]
    public function itCascadesDisposalToChildren(): void
    {
        $parent = $this->createRemoteObject('parent-id', 'parent');
        $child1 = $this->createRemoteObject('child1-id', 'child');
        $child2 = $this->createRemoteObject('child2-id', 'child');

        $parent->addChild($child1);
        $parent->addChild($child2);

        $this->assertFalse($parent->isDisposed());
        $this->assertFalse($child1->isDisposed());
        $this->assertFalse($child2->isDisposed());

        $parent->dispose();

        $this->assertTrue($parent->isDisposed());
        $this->assertTrue($child1->isDisposed());
        $this->assertTrue($child2->isDisposed());
    }

    #[Test]
    public function itIsIdempotentWhenDisposing(): void
    {
        $object = $this->createRemoteObject('test-id', 'test-type');

        $object->dispose();
        $this->assertTrue($object->isDisposed());

        // Should not throw or cause issues
        $object->dispose();
        $this->assertTrue($object->isDisposed());
    }

    #[Test]
    public function itThrowsWhenOperatingOnDisposedObject(): void
    {
        $parent = $this->createRemoteObject('parent-id', 'parent');
        $child = $this->createRemoteObject('child-id', 'child');

        $parent->dispose();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot perform addChild on disposed parent (id: parent-id)');

        $parent->addChild($child);
    }

    #[Test]
    public function itRemovesChildFromParentOnDisposal(): void
    {
        $parent = $this->createRemoteObject('parent-id', 'parent');
        $child = $this->createRemoteObject('child-id', 'child');

        $parent->addChild($child);
        $this->assertContains($child, $parent->getChildren());

        $child->dispose();

        $this->assertNotContains($child, $parent->getChildren());
        $this->assertNull($child->getParent());
        $this->assertTrue($child->isDisposed());
        $this->assertFalse($parent->isDisposed());
    }

    #[Test]
    public function itCallsOnDisposeWhenDisposing(): void
    {
        $object = $this->createRemoteObjectWithOnDispose();

        $object->dispose();

        $this->assertTrue($object->wasOnDisposeCalled());
    }

    private function createRemoteObject(string $id, string $type): TestRemoteObject
    {
        return new TestRemoteObject($this->transport, $id, $type);
    }

    private function createRemoteObjectWithOnDispose(): TestRemoteObjectWithOnDispose
    {
        return new TestRemoteObjectWithOnDispose($this->transport, 'test-id', 'test-type');
    }
}

/**
 * Test implementation of RemoteObject.
 */
class TestRemoteObject extends RemoteObject
{
    // Public getters for testing protected methods
}

/**
 * Test implementation with onDispose tracking.
 */
class TestRemoteObjectWithOnDispose extends RemoteObject
{
    private bool $onDisposeCalled = false;

    protected function onDispose(): void
    {
        $this->onDisposeCalled = true;
    }

    public function wasOnDisposeCalled(): bool
    {
        return $this->onDisposeCalled;
    }
}