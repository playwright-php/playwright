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
     * @param array<string, mixed> $options
     */
    public function getByAltText(string $text, array $options = []): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByLabel(string $text, array $options = []): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByPlaceholder(string $text, array $options = []): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByRole(string $role, array $options = []): LocatorInterface;

    public function getByTestId(string $testId): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByText(string $text, array $options = []): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByTitle(string $text, array $options = []): LocatorInterface;

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
