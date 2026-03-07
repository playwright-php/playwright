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

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToWriteFile;
use Playwright\Exception\RuntimeException;

final readonly class FlysystemFilesystem implements FilesystemInterface
{
    private Filesystem $filesystem;

    public function __construct(string $directory)
    {
        $this->filesystem = new Filesystem(new LocalFilesystemAdapter($directory));
    }

    public function write(string $path, string $content): string
    {
        try {
            $this->filesystem->write($path, $content);
        } catch (FilesystemException|UnableToWriteFile $e) {
            throw new RuntimeException(sprintf('Failed to write screenshot to file: %s', $path), 0, $e);
        }

        return $path;
    }
}
