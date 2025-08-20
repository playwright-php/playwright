<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface TransportInterface
{
    public function connect(): void;

    public function disconnect(): void;

    public function send(array $message): array;

    public function sendAsync(array $message): void;

    public function isConnected(): bool;

    public function processEvents(): void;
}
