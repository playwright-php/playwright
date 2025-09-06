<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Frame;

use PlaywrightPHP\Locator\LocatorInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface FrameLocatorInterface
{
    /**
     * Create a locator within this frame.
     */
    public function locator(string $selector): LocatorInterface;

    /**
     * Get the first frame matching the selector.
     */
    public function first(): self;

    /**
     * Get the last frame matching the selector.
     */
    public function last(): self;

    /**
     * Get the nth frame matching the selector (0-indexed).
     */
    public function nth(int $index): self;

    /**
     * Create a nested frame locator.
     */
    public function frameLocator(string $selector): self;

    /**
     * Get the frame selector.
     */
    public function getSelector(): string;

    /**
     * Get locator for the frame element itself.
     */
    public function owner(): LocatorInterface;
}
