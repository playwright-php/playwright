<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Internal;

/**
 * Optional static registry to link parent/child relationships and manage disposal.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class OwnershipRegistry
{
    /**
     * @var array<string, RemoteObject>
     */
    private static array $objects = [];

    /**
     * Register a remote object.
     */
    public static function register(RemoteObject $object): void
    {
        self::$objects[$object->getRemoteId()] = $object;
    }

    /**
     * Link a parent and child object.
     */
    public static function linkParentChild(RemoteObject $parent, RemoteObject $child): void
    {
        self::register($parent);
        self::register($child);
        $parent->addChild($child);
    }

    /**
     * Dispose an object and cascade to its children.
     */
    public static function disposeCascade(string $remoteId): void
    {
        $object = self::$objects[$remoteId] ?? null;
        if (null !== $object) {
            $object->dispose();
            unset(self::$objects[$remoteId]);
        }
    }

    /**
     * Get a registered object by ID.
     */
    public static function get(string $remoteId): ?RemoteObject
    {
        return self::$objects[$remoteId] ?? null;
    }

    /**
     * Reset the registry (useful for testing).
     */
    public static function reset(): void
    {
        self::$objects = [];
    }

    /**
     * Get all registered objects.
     *
     * @return array<string, RemoteObject>
     */
    public static function getAll(): array
    {
        return self::$objects;
    }
}
