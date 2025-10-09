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

namespace Playwright\Frame;

use Playwright\Locator\LocatorInterface;

interface FrameInterface
{
    /**
     * Create a locator within this frame.
     */
    public function locator(string $selector): LocatorInterface;

    /**
     * Create a nested frame locator from this frame.
     */
    public function frameLocator(string $selector): FrameLocatorInterface;

    /**
     * Get locator for the frame element itself (the <iframe> owner).
     */
    public function owner(): LocatorInterface;

    /**
     * The frame's name attribute.
     */
    public function name(): string;

    /**
     * The current URL loaded in this frame.
     */
    public function url(): string;

    /**
     * Whether the frame is detached from the DOM.
     */
    public function isDetached(): bool;

    /**
     * Wait for a particular load state in this frame.
     *
     * @param array<string, mixed> $options
     */
    public function waitForLoadState(string $state = 'load', array $options = []): self;

    /**
     * Get the parent frame, if any.
     */
    public function parentFrame(): ?FrameInterface;

    /**
     * Get the child frames of this frame.
     *
     * @return array<FrameInterface>
     */
    public function childFrames(): array;

    public function __toString(): string;
}
