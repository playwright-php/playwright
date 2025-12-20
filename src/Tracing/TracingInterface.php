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

use Playwright\Tracing\Options\StartChunkOptions;
use Playwright\Tracing\Options\StartOptions;
use Playwright\Tracing\Options\StopChunkOptions;
use Playwright\Tracing\Options\StopOptions;

interface TracingInterface
{
    /**
     * Start tracing.
     *
     * @param array<string, mixed>|StartOptions $options
     */
    public function start(array|StartOptions $options = []): void;

    /**
     * Start a new trace chunk. If tracing was already started, this creates a new trace chunk.
     *
     * @param array<string, mixed>|StartChunkOptions $options
     */
    public function startChunk(array|StartChunkOptions $options = []): void;

    /**
     * Stop tracing.
     *
     * @param array<string, mixed>|StopOptions $options
     */
    public function stop(array|StopOptions $options = []): void;

    /**
     * Stop the trace chunk. See startChunk() for more details.
     *
     * @param array<string, mixed>|StopChunkOptions $options
     */
    public function stopChunk(array|StopChunkOptions $options = []): void;

    /**
     * @deprecated Use test.step() instead
     */
    public function group(string $name, string $location): void;

    /**
     * @deprecated Use test.step() instead
     */
    public function groupEnd(): void;
}
