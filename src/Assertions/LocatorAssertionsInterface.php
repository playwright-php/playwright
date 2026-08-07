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

namespace Playwright\Assertions;

interface LocatorAssertionsInterface
{
    /**
     * Asserts the element is present in the DOM, visible or not.
     *
     * Weaker than toBeVisible(): a display:none element is attached.
     */
    public function toBeAttached(?AssertionOptions $options = null): self;

    /**
     * Asserts the element can be edited: enabled, not readonly, and accepting input.
     */
    public function toBeEditable(?AssertionOptions $options = null): self;

    /**
     * Asserts the element intersects the viewport.
     *
     * AssertionOptions::$ratio sets the fraction of the element's own area that
     * must be visible, between 0 and 1. Out of that range throws.
     */
    public function toBeInViewport(?AssertionOptions $options = null): self;

    public function toBeVisible(?AssertionOptions $options = null): self;

    public function toBeHidden(?AssertionOptions $options = null): self;

    /**
     * @param string|array<string> $expected
     *
     * @return $this
     */
    public function toHaveText(string|array $expected, ?AssertionOptions $options = null): self;

    public function toHaveCount(int $expected, ?AssertionOptions $options = null): self;

    /**
     * Asserts a JavaScript property of the element equals the expected value.
     *
     * Reads the live DOM property, not the HTML attribute: an input the user
     * typed into has value "typed" here and "" as an attribute. Arrays and
     * objects are compared structurally, key by key.
     */
    public function toHaveJSProperty(string $name, mixed $expected, ?AssertionOptions $options = null): self;

    /**
     * @param array<string>|string $expected
     */
    public function toHaveValues(array|string $expected, ?AssertionOptions $options = null): self;

    /**
     * Asserts the element's ARIA role.
     *
     * Uses the explicit role attribute when present, otherwise the role implied
     * by the tag name. This is not the full computed accessibility role: roles
     * derived from attributes, such as an input's type, are not resolved.
     */
    public function toHaveRole(string $role, ?AssertionOptions $options = null): self;

    /**
     * Asserts the element carries the given classes, among others.
     *
     * Space-separated tokens are all required, in any order; extra classes on
     * the element are ignored. An expectation with no token throws.
     */
    public function toContainClass(string $expected, ?AssertionOptions $options = null): self;

    public function not(): self;
}
