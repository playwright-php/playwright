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

namespace Playwright\Tracing;

interface TracingInterface
{
    /**
     * Start tracing.
     *
     * @param array{name?: string, screenshots?: bool, snapshots?: bool, sources?: bool, title?: string} $options
     */
    public function start(array $options = []): void;

    /**
     * Start a new trace chunk. If tracing was already started, this creates a new trace chunk.
     *
     * @param array{name?: string, title?: string} $options
     */
    public function startChunk(array $options = []): void;

    /**
     * Stop tracing.
     *
     * @param array{path?: string} $options
     */
    public function stop(array $options = []): void;

    /**
     * Stop the trace chunk. See startChunk() for more details.
     *
     * @param array{path?: string} $options
     */
    public function stopChunk(array $options = []): void;

    /**
     * @deprecated Use test.step() instead
     */
    public function group(string $name, string $location): void;

    /**
     * @deprecated Use test.step() instead
     */
    public function groupEnd(): void;
}
