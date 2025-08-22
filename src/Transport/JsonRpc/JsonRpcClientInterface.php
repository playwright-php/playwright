<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport\JsonRpc;

/**
 * Interface for JSON-RPC client implementations.
 *
 * @experimental
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface JsonRpcClientInterface
{
    /**
     * Send a JSON-RPC request and wait for response.
     *
     * @param array<string, mixed>|null $params
     *
     * @return array<string, mixed>
     */
    public function send(string $method, ?array $params = null, ?float $timeoutMs = null): array;

    /**
     * Send a raw message in the original format and wait for response.
     *
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function sendRaw(array $message, ?float $timeoutMs = null): array;

    /**
     * @return array<int, array{method: string, timestamp: float, age: float}>
     */
    public function getPendingRequests(): array;

    public function cancelPendingRequests(): void;
}
