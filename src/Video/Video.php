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

namespace Playwright\Video;

use Playwright\Transport\TransportInterface;

/**
 * Represents a recorded page video.
 *
 * The video exposes the local recording path and lets callers save or delete the file.
 * File operations wait for the browser to finish writing the recording when needed.
 */
final class Video implements VideoInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $videoId,
        private readonly string $videoPath,
    ) {
    }

    public function delete(): void
    {
        $this->transport->send([
            'action' => 'video.delete',
            'videoId' => $this->videoId,
        ]);
        if (file_exists($this->videoPath)) {
            unlink($this->videoPath);
        }
    }

    public function path(): string
    {
        return $this->videoPath;
    }

    public function saveAs(string $path): void
    {
        $this->transport->send([
            'action' => 'video.saveAs',
            'videoId' => $this->videoId,
            'path' => $path,
        ]);
        if (file_exists($this->videoPath) && $this->videoPath !== $path) {
            $directory = dirname($path);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            copy($this->videoPath, $path);
        }
    }
}
