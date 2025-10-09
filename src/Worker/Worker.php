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

use Playwright\JSHandle\JSHandle;
use Playwright\JSHandle\JSHandleInterface;
use Playwright\Transport\TransportInterface;

/**
 * @see https://playwright.dev/docs/api/class-worker
 */
final class Worker implements WorkerInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $workerId,
        private readonly string $workerUrl,
    ) {
    }

    public function url(): string
    {
        return $this->workerUrl;
    }

    public function evaluate(string $expression, mixed $arg = null): mixed
    {
        $response = $this->transport->send([
            'action' => 'worker.evaluate',
            'workerId' => $this->workerId,
            'expression' => $expression,
            'arg' => $arg,
        ]);

        return $response['result'] ?? null;
    }

    public function evaluateHandle(string $expression, mixed $arg = null): JSHandleInterface
    {
        $response = $this->transport->send([
            'action' => 'worker.evaluateHandle',
            'workerId' => $this->workerId,
            'expression' => $expression,
            'arg' => $arg,
        ]);

        $handleId = $response['handleId'] ?? '';
        if (!is_string($handleId)) {
            throw new \RuntimeException('Invalid handleId returned from worker.evaluateHandle');
        }

        return new JSHandle($this->transport, $handleId);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public function waitForEvent(string $event, ?callable $predicate = null, array $options = []): array
    {
        $response = $this->transport->send([
            'action' => 'worker.waitForEvent',
            'workerId' => $this->workerId,
            'event' => $event,
            'options' => $options,
        ]);

        $data = $response['eventData'] ?? null;
        if (!is_array($data)) {
            return [];
        }
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
