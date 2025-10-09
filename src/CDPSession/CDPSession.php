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

namespace Playwright\CDPSession;

use Playwright\Transport\TransportInterface;

/**
 * @see https://playwright.dev/docs/api/class-cdpsession
 */
final class CDPSession implements CDPSessionInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $sessionId,
    ) {
    }

    public function detach(): void
    {
        $this->transport->send([
            'action' => 'cdpSession.detach',
            'sessionId' => $this->sessionId,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function send(string $method, array $params = []): array
    {
        $response = $this->transport->send([
            'action' => 'cdpSession.send',
            'sessionId' => $this->sessionId,
            'method' => $method,
            'params' => $params,
        ]);

        $result = $response['result'] ?? null;
        if (!is_array($result)) {
            return [];
        }

        $sanitized = [];
        foreach ($result as $key => $value) {
            if (is_string($key)) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
