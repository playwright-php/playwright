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

namespace Playwright\WebStorage;

/**
 * Reads and writes the storage of the page's current origin.
 *
 * Every method throws when the page sits on an origin that has no storage,
 * such as `about:blank` or a `data:` URL.
 *
 * @see https://playwright.dev/docs/api/class-webstorage
 */
interface WebStorageInterface
{
    /**
     * Removes every item, leaving the other storage of the same origin untouched.
     */
    public function clear(): void;

    /**
     * Returns null when nothing is stored under that name.
     */
    public function getItem(string $name): ?string;

    /**
     * Returns every item, in the order the browser reports them.
     *
     * @return list<array{name: string, value: string}>
     */
    public function items(): array;

    /**
     * Does nothing when no item is stored under that name.
     */
    public function removeItem(string $name): void;

    /**
     * Overwrites any value already stored under that name.
     */
    public function setItem(string $name, string $value): void;
}
