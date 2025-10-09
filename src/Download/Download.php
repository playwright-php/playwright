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

namespace Playwright\Download;

use Playwright\Exception\RuntimeException;
use Playwright\Page\PageInterface;
use Playwright\Transport\TransportInterface;

/**
 * Download class for Playwright PHP.
 */
final class Download implements DownloadInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly PageInterface $page,
        private readonly string $downloadId,
        private readonly array $data = [],
    ) {
    }

    public function cancel(): void
    {
        $this->transport->send([
            'action' => 'download.cancel',
            'downloadId' => $this->downloadId,
        ]);
    }

    /**
     * @return resource|null
     */
    public function createReadStream()
    {
        $response = $this->transport->send([
            'action' => 'download.readStream',
            'downloadId' => $this->downloadId,
        ]);

        $encoded = $response['stream'] ?? null;
        if (!is_string($encoded)) {
            return null;
        }

        $binary = base64_decode($encoded, true);
        if (false === $binary) {
            return null;
        }

        $stream = fopen('php://temp', 'r+');
        if (false === $stream) {
            throw new RuntimeException('Failed to create read stream for download');
        }

        fwrite($stream, $binary);
        rewind($stream);

        return $stream;
    }

    public function delete(): void
    {
        $this->transport->send([
            'action' => 'download.delete',
            'downloadId' => $this->downloadId,
        ]);
    }

    public function failure(): ?string
    {
        $response = $this->transport->send([
            'action' => 'download.failure',
            'downloadId' => $this->downloadId,
        ]);

        return isset($response['error']) && is_string($response['error']) ? $response['error'] : null;
    }

    public function page(): PageInterface
    {
        return $this->page;
    }

    public function path(): ?string
    {
        $response = $this->transport->send([
            'action' => 'download.path',
            'downloadId' => $this->downloadId,
        ]);

        return isset($response['path']) && is_string($response['path']) ? $response['path'] : null;
    }

    public function saveAs(string $path): void
    {
        $this->transport->send([
            'action' => 'download.saveAs',
            'downloadId' => $this->downloadId,
            'path' => $path,
        ]);
    }

    public function suggestedFilename(): string
    {
        $filename = $this->data['suggestedFilename'] ?? null;

        return is_string($filename) ? $filename : '';
    }

    public function url(): string
    {
        $url = $this->data['url'] ?? null;

        return is_string($url) ? $url : '';
    }
}
