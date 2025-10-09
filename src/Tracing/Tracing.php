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

use Playwright\Transport\TransportInterface;

final class Tracing implements TracingInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $contextId,
    ) {
    }

    public function start(array $options = []): void
    {
        $this->transport->send([
            'action' => 'tracingStart',
            'contextId' => $this->contextId,
            'options' => $options,
        ]);
    }

    public function startChunk(array $options = []): void
    {
        $this->transport->send([
            'action' => 'tracingStartChunk',
            'contextId' => $this->contextId,
            'options' => $options,
        ]);
    }

    public function stop(array $options = []): void
    {
        $this->transport->send([
            'action' => 'tracingStop',
            'contextId' => $this->contextId,
            'options' => $options,
        ]);
    }

    public function stopChunk(array $options = []): void
    {
        $this->transport->send([
            'action' => 'tracingStopChunk',
            'contextId' => $this->contextId,
            'options' => $options,
        ]);
    }

    /**
     * @deprecated Use test.step() instead
     */
    public function group(string $name, string $location): void
    {
        $this->transport->send([
            'action' => 'tracingGroup',
            'contextId' => $this->contextId,
            'name' => $name,
            'location' => $location,
        ]);
    }

    /**
     * @deprecated Use test.step() instead
     */
    public function groupEnd(): void
    {
        $this->transport->send([
            'action' => 'tracingGroupEnd',
            'contextId' => $this->contextId,
        ]);
    }
}
