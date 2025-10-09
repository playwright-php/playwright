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

namespace Playwright\Worker;

use Playwright\JSHandle\JSHandleInterface;

/**
 * @see https://playwright.dev/docs/api/class-worker
 */
interface WorkerInterface
{
    /**
     * Returns the URL of the web worker.
     */
    public function url(): string;

    /**
     * Evaluates expression in the worker context.
     *
     * @param string $expression JavaScript expression to evaluate
     * @param mixed  $arg        Optional argument to pass to the expression
     *
     * @return mixed The result of the evaluation
     */
    public function evaluate(string $expression, mixed $arg = null): mixed;

    /**
     * Evaluates expression in the worker context and returns a JSHandle.
     *
     * @param string $expression JavaScript expression to evaluate
     * @param mixed  $arg        Optional argument to pass to the expression
     *
     * @return JSHandleInterface The JSHandle for the result
     */
    public function evaluateHandle(string $expression, mixed $arg = null): JSHandleInterface;

    /**
     * Waits for event to fire and passes its value into the predicate function.
     *
     * @param string               $event     Event name
     * @param callable|null        $predicate Optional predicate function
     * @param array<string, mixed> $options   Options including timeout
     *
     * @return array<string, mixed> Event data
     */
    public function waitForEvent(string $event, ?callable $predicate = null, array $options = []): array;
}
