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

namespace Playwright\Selector;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface SelectorsInterface
{
    /**
     * Register a custom selector engine.
     *
     * @param array<string, mixed> $options
     */
    public function register(string $name, string $script, array $options = []): void;

    /**
     * Set the test id attribute name.
     */
    public function setTestIdAttribute(string $attributeName): void;

    /**
     * Get the current test id attribute name.
     */
    public function getTestIdAttribute(): string;
}
