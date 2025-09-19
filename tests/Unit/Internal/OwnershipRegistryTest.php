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
use PlaywrightPHP\Internal\OwnershipRegistry;
use PlaywrightPHP\Internal\RemoteObject;
use PlaywrightPHP\Transport\TransportInterface;

#[CoversClass(OwnershipRegistry::class)]
class OwnershipRegistryTest extends TestCase
{
    private TransportInterface $transport;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        OwnershipRegistry::reset();
    }

    protected function tearDown(): void
    {
        OwnershipRegistry::reset();
    }

    #[Test]
    public function itCanRegisterObjects(): void
    {
        $object = $this->createRemoteObject('test-id', 'test-type');

        OwnershipRegistry::register($object);

        $this->assertSame($object, OwnershipRegistry::get('test-id'));
        $this->assertContains($object, OwnershipRegistry::getAll());
    }

    #[Test]
    public function itCanLinkParentChild(): void
    {
        $parent = $this->createRemoteObject('parent-id', 'parent');
        $child = $this->createRemoteObject('child-id', 'child');

        OwnershipRegistry::linkParentChild($parent, $child);

        $this->assertSame($parent, OwnershipRegistry::get('parent-id'));
        $this->assertSame($child, OwnershipRegistry::get('child-id'));
        $this->assertSame($parent, $child->getParent());
        $this->assertContains($child, $parent->getChildren());
    }

    #[Test]
    public function itCanDisposeCascade(): void
    {
        $parent = $this->createRemoteObject('parent-id', 'parent');
        $child = $this->createRemoteObject('child-id', 'child');

        OwnershipRegistry::linkParentChild($parent, $child);

        $this->assertFalse($parent->isDisposed());
        $this->assertFalse($child->isDisposed());

        OwnershipRegistry::disposeCascade('parent-id');

        $this->assertTrue($parent->isDisposed());
        $this->assertTrue($child->isDisposed());
        $this->assertNull(OwnershipRegistry::get('parent-id'));
    }

    #[Test]
    public function itHandlesNonExistentObjectInDisposeCascade(): void
    {
        // Should not throw
        OwnershipRegistry::disposeCascade('non-existent-id');
        
        $this->assertNull(OwnershipRegistry::get('non-existent-id'));
    }

    #[Test]
    public function itCanReset(): void
    {
        $object = $this->createRemoteObject('test-id', 'test-type');
        OwnershipRegistry::register($object);

        $this->assertNotEmpty(OwnershipRegistry::getAll());

        OwnershipRegistry::reset();

        $this->assertEmpty(OwnershipRegistry::getAll());
        $this->assertNull(OwnershipRegistry::get('test-id'));
    }

    private function createRemoteObject(string $id, string $type): TestRemoteObjectRegistry
    {
        return new TestRemoteObjectRegistry($this->transport, $id, $type);
    }
}

/**
 * Test implementation of RemoteObject for registry tests.
 */
class TestRemoteObjectRegistry extends RemoteObject
{
    // Public getters for testing protected methods
}
