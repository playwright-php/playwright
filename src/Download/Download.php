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

use Playwright\Exception\ProtocolErrorException;
use Playwright\Exception\RuntimeException;
use Playwright\Page\PageInterface;
use Playwright\Transport\TransportInterface;

/**
 * Download class for Playwright PHP.
 */
final class Download implements DownloadInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly PageInterface $page,
        private readonly string $downloadId,
        private readonly string $url,
        private readonly string $suggestedFilename,
    ) {
    }

    public function cancel(): void
    {
        $this->transport->send([
            'action' => 'downloadCancel',
            'downloadId' => $this->downloadId,
        ]);
    }

    /**
     * @return resource
     */
    public function createReadStream()
    {
        $response = $this->transport->send([
            'action' => 'downloadReadStream',
            'downloadId' => $this->downloadId,
        ]);

        if (!is_string($response['stream'])) {
            throw new ProtocolErrorException('Invalid stream returned from transport', 0);
        }

        $stream = fopen('php://temp', 'r+');
        if (false === $stream) {
            throw new RuntimeException('Failed to create read stream for download');
        }

        fwrite($stream, base64_decode($response['stream'], true) ?: '');
        rewind($stream);

        return $stream;
    }

    public function delete(): void
    {
        $this->transport->send([
            'action' => 'downloadDelete',
            'downloadId' => $this->downloadId,
        ]);
    }

    public function failure(): ?string
    {
        $response = $this->transport->send([
            'action' => 'downloadFailure',
            'downloadId' => $this->downloadId,
        ]);

        $error = $response['error'] ?? null;

        return is_string($error) ? $error : null;
    }

    public function page(): PageInterface
    {
        return $this->page;
    }

    public function path(): string
    {
        $response = $this->transport->send([
            'action' => 'downloadPath',
            'downloadId' => $this->downloadId,
        ]);

        if (!is_string($response['path'])) {
            throw new ProtocolErrorException('Invalid path returned from transport', 0);
        }

        return $response['path'];
    }

    public function saveAs(string $path): void
    {
        $this->transport->send([
            'action' => 'downloadSaveAs',
            'downloadId' => $this->downloadId,
            'path' => $path,
        ]);
    }

    public function suggestedFilename(): string
    {
        return $this->suggestedFilename;
    }

    public function url(): string
    {
        return $this->url;
    }
}
