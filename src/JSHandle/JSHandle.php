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

use Playwright\Transport\TransportInterface;

/**
 * @see https://playwright.dev/docs/api/class-jshandle
 */
final class JSHandle implements JSHandleInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $handleId,
    ) {
    }

    public function asElement(): ?object
    {
        $response = $this->transport->send([
            'action' => 'jsHandle.asElement',
            'handleId' => $this->handleId,
        ]);

        $element = $response['element'] ?? null;

        return \is_object($element) ? $element : null;
    }

    public function dispose(): void
    {
        $this->transport->send([
            'action' => 'jsHandle.dispose',
            'handleId' => $this->handleId,
        ]);
    }

    public function evaluate(string $expression, mixed $arg = null): mixed
    {
        $response = $this->transport->send([
            'action' => 'jsHandle.evaluate',
            'handleId' => $this->handleId,
            'expression' => $expression,
            'arg' => $arg,
        ]);

        return $response['result'] ?? null;
    }

    public function evaluateHandle(string $expression, mixed $arg = null): JSHandleInterface
    {
        $response = $this->transport->send([
            'action' => 'jsHandle.evaluateHandle',
            'handleId' => $this->handleId,
            'expression' => $expression,
            'arg' => $arg,
        ]);

        $newHandleId = $response['handleId'] ?? '';
        if (!is_string($newHandleId)) {
            throw new \RuntimeException('Invalid handleId returned from jsHandle.evaluateHandle');
        }

        return new JSHandle($this->transport, $newHandleId);
    }

    public function getProperties(): array
    {
        $response = $this->transport->send([
            'action' => 'jsHandle.getProperties',
            'handleId' => $this->handleId,
        ]);

        $properties = [];
        if (is_array($response['properties'])) {
            foreach ($response['properties'] as $name => $handleId) {
                if (is_string($name) && is_string($handleId)) {
                    $properties[$name] = new JSHandle($this->transport, $handleId);
                }
            }
        }

        return $properties;
    }

    public function getProperty(string $propertyName): JSHandleInterface
    {
        $response = $this->transport->send([
            'action' => 'jsHandle.getProperty',
            'handleId' => $this->handleId,
            'propertyName' => $propertyName,
        ]);

        $handleId = $response['handleId'] ?? '';
        if (!is_string($handleId)) {
            throw new \RuntimeException('Invalid handleId returned from jsHandle.getProperty');
        }

        return new JSHandle($this->transport, $handleId);
    }

    public function jsonValue(): mixed
    {
        $response = $this->transport->send([
            'action' => 'jsHandle.jsonValue',
            'handleId' => $this->handleId,
        ]);

        return $response['value'] ?? null;
    }
}
