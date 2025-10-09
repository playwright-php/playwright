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

namespace Playwright\JSHandle;

/**
 * @see https://playwright.dev/docs/api/class-jshandle
 */
interface JSHandleInterface
{
    /**
     * Returns the element handle if this is an element, null otherwise.
     */
    public function asElement(): ?object;

    /**
     * Disposes the handle.
     */
    public function dispose(): void;

    /**
     * Evaluates expression in the context of the handle.
     *
     * @param string $expression JavaScript expression to evaluate
     * @param mixed  $arg        Optional argument to pass to the expression
     *
     * @return mixed The result of the evaluation
     */
    public function evaluate(string $expression, mixed $arg = null): mixed;

    /**
     * Evaluates expression in the context of the handle and returns a JSHandle.
     *
     * @param string $expression JavaScript expression to evaluate
     * @param mixed  $arg        Optional argument to pass to the expression
     *
     * @return JSHandleInterface The JSHandle for the result
     */
    public function evaluateHandle(string $expression, mixed $arg = null): JSHandleInterface;

    /**
     * Returns a map with property names as keys and JSHandle instances for the property values.
     *
     * @return array<string, JSHandleInterface>
     */
    public function getProperties(): array;

    /**
     * Fetches a single property from the referenced object.
     *
     * @param string $propertyName Property name to get
     *
     * @return JSHandleInterface The JSHandle for the property
     */
    public function getProperty(string $propertyName): JSHandleInterface;

    /**
     * Returns a JSON representation of the object.
     *
     * @return mixed The JSON value
     */
    public function jsonValue(): mixed;
}
