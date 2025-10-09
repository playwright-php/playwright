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

namespace Playwright\BrowserServer;

use Playwright\Transport\TransportInterface;

final class BrowserServer implements BrowserServerInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $serverId,
        private readonly string $endpoint,
        private readonly ?int $pid = null,
    ) {
    }

    public function wsEndpoint(): string
    {
        return $this->endpoint;
    }

    public function process(): ?int
    {
        return $this->pid;
    }

    public function close(): void
    {
        $this->transport->send([
            'action' => 'browserServer.close',
            'serverId' => $this->serverId,
        ]);
    }

    public function kill(): void
    {
        $this->transport->send([
            'action' => 'browserServer.kill',
            'serverId' => $this->serverId,
        ]);
    }
}
