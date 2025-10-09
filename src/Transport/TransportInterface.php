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

namespace Playwright\Transport;

interface TransportInterface
{
    public function connect(): void;

    public function disconnect(): void;

    /**
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function send(array $message): array;

    /**
     * @param array<string, mixed> $message
     */
    public function sendAsync(array $message): void;

    public function isConnected(): bool;

    public function processEvents(): void;
}
