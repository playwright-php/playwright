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

namespace Playwright\Media\Filesystem;

use Playwright\Exception\RuntimeException;

final readonly class LocalFilesystemFilesystem implements FilesystemInterface
{
    public function __construct(private string $directory)
    {
        $this->ensureDirectoryExists($this->directory);
    }

    public function write(string $path, string $content): string
    {
        if (!str_contains($path, $this->directory)) {
            $path = $this->directory.DIRECTORY_SEPARATOR.$path;
        }

        $this->ensureDirectoryExists(dirname($path));

        file_put_contents($path, $content) ?: throw new RuntimeException(sprintf('Failed to write screenshot to file: %s', $path));

        return $path;
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Failed to create screenshot directory: %s', $directory));
        }
    }
}
