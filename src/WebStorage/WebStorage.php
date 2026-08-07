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

namespace Playwright\WebStorage;

use Playwright\Exception\PlaywrightException;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Transport\TransportInterface;

final class WebStorage implements WebStorageInterface
{
    /**
     * @param 'localStorage'|'sessionStorage' $storage
     */
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $pageId,
        private readonly string $storage,
    ) {
    }

    public function clear(): void
    {
        $this->send([
            'action' => 'webStorage.clear',
            'pageId' => $this->pageId,
            'storage' => $this->storage,
        ]);
    }

    public function getItem(string $name): ?string
    {
        $response = $this->send([
            'action' => 'webStorage.getItem',
            'pageId' => $this->pageId,
            'storage' => $this->storage,
            'name' => $name,
        ]);

        $value = $response['value'] ?? null;

        return is_string($value) ? $value : null;
    }

    public function items(): array
    {
        $response = $this->send([
            'action' => 'webStorage.items',
            'pageId' => $this->pageId,
            'storage' => $this->storage,
        ]);

        $items = $response['items'] ?? null;
        if (!is_array($items)) {
            throw new ProtocolErrorException('Invalid web storage items response', 0);
        }

        $result = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new ProtocolErrorException('Invalid web storage item response', 0);
            }

            $name = $item['name'] ?? null;
            $value = $item['value'] ?? null;
            if (!is_string($name) || !is_string($value)) {
                throw new ProtocolErrorException('Invalid web storage item response', 0);
            }

            $result[] = ['name' => $name, 'value' => $value];
        }

        return $result;
    }

    public function removeItem(string $name): void
    {
        $this->send([
            'action' => 'webStorage.removeItem',
            'pageId' => $this->pageId,
            'storage' => $this->storage,
            'name' => $name,
        ]);
    }

    public function setItem(string $name, string $value): void
    {
        $this->send([
            'action' => 'webStorage.setItem',
            'pageId' => $this->pageId,
            'storage' => $this->storage,
            'name' => $name,
            'value' => $value,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function send(array $payload): array
    {
        $response = $this->transport->send($payload);

        if (isset($response['error'])) {
            $error = $response['error'];

            throw new PlaywrightException(is_string($error) ? $error : 'Unknown Playwright server error');
        }

        return $response;
    }
}
