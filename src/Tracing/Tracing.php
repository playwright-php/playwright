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
use Playwright\Tracing\Options\StartHarOptions;
use Playwright\Tracing\Options\StartOptions;
use Playwright\Tracing\Options\StopChunkOptions;
use Playwright\Tracing\Options\StopOptions;
use Playwright\Transport\TransportInterface;

final class Tracing implements TracingInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $contextId,
    ) {
    }

    public function start(array|StartOptions $options = []): void
    {
        $options = StartOptions::from($options);
        $this->transport->send([
            'action' => 'tracingStart',
            'contextId' => $this->contextId,
            'options' => $options->toArray(),
        ]);
    }

    public function startChunk(array|StartChunkOptions $options = []): void
    {
        $options = StartChunkOptions::from($options);
        $this->transport->send([
            'action' => 'tracingStartChunk',
            'contextId' => $this->contextId,
            'options' => $options->toArray(),
        ]);
    }

    public function stop(array|StopOptions $options = []): void
    {
        $options = StopOptions::from($options);
        $this->transport->send([
            'action' => 'tracingStop',
            'contextId' => $this->contextId,
            'options' => $options->toArray(),
        ]);
    }

    public function stopChunk(array|StopChunkOptions $options = []): void
    {
        $options = StopChunkOptions::from($options);
        $this->transport->send([
            'action' => 'tracingStopChunk',
            'contextId' => $this->contextId,
            'options' => $options->toArray(),
        ]);
    }

    public function startHar(string $path, array|StartHarOptions $options = []): void
    {
        $options = StartHarOptions::from($options);
        $this->transport->send([
            'action' => 'tracingStartHar',
            'contextId' => $this->contextId,
            'path' => $path,
            'options' => $options->toArray(),
        ]);
    }

    public function stopHar(): void
    {
        $this->transport->send([
            'action' => 'tracingStopHar',
            'contextId' => $this->contextId,
        ]);
    }

    public function group(string $name, ?string $location = null): void
    {
        $payload = [
            'action' => 'tracingGroup',
            'contextId' => $this->contextId,
            'name' => $name,
        ];
        if (null !== $location) {
            $payload['location'] = $location;
        }

        $this->transport->send($payload);
    }

    public function groupEnd(): void
    {
        $this->transport->send([
            'action' => 'tracingGroupEnd',
            'contextId' => $this->contextId,
        ]);
    }
}
