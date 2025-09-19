<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Internal;

use PlaywrightPHP\Exception\RuntimeException;
use PlaywrightPHP\Transport\TransportInterface;

/**
 * Base class modeling a remote Playwright object with ownership management.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
abstract class RemoteObject
{
    private bool $disposed = false;
    private ?RemoteObject $parent = null;

    /**
     * @var array<RemoteObject>
     */
    private array $children = [];

    public function __construct(
        protected readonly TransportInterface $transport,
        protected readonly string $remoteId,
        protected readonly string $remoteType,
    ) {
    }

    /**
     * Add a child object to this parent.
     */
    public function addChild(RemoteObject $child): void
    {
        $this->assertAlive('addChild');
        $child->parent = $this;
        $this->children[] = $child;
    }

    /**
     * Remove a child object from this parent.
     */
    public function removeChild(RemoteObject $child): void
    {
        $key = array_search($child, $this->children, true);
        if (false !== $key) {
            unset($this->children[$key]);
            $child->parent = null;
        }
    }

    /**
     * Dispose this object and cascade to all children.
     */
    public function dispose(): void
    {
        if ($this->disposed) {
            return; // Idempotent
        }

        // First, dispose all children
        foreach ($this->children as $child) {
            $child->dispose();
        }
        $this->children = [];

        // Remove from parent
        if (null !== $this->parent) {
            $this->parent->removeChild($this);
            $this->parent = null;
        }

        // Mark as disposed before calling onDispose to prevent infinite recursion
        $this->disposed = true;

        // Allow subclasses to perform cleanup
        $this->onDispose();
    }

    /**
     * Check if the object is disposed.
     */
    public function isDisposed(): bool
    {
        return $this->disposed;
    }

    /**
     * Assert that the object is still alive (not disposed).
     *
     * @throws RuntimeException if the object has been disposed
     */
    protected function assertAlive(string $action = 'operation'): void
    {
        if ($this->disposed) {
            throw new RuntimeException(
                sprintf(
                    'Cannot perform %s on disposed %s (id: %s)',
                    $action,
                    $this->remoteType,
                    $this->remoteId
                )
            );
        }
    }

    /**
     * Get the remote ID.
     */
    public function getRemoteId(): string
    {
        return $this->remoteId;
    }

    /**
     * Get the remote type.
     */
    public function getRemoteType(): string
    {
        return $this->remoteType;
    }

    /**
     * Get the parent object.
     */
    public function getParent(): ?RemoteObject
    {
        return $this->parent;
    }

    /**
     * Get all children objects.
     *
     * @return array<RemoteObject>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Called when the object is being disposed. Override to perform cleanup.
     */
    protected function onDispose(): void
    {
        // Default implementation does nothing
    }
}
