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

namespace Playwright\Browser;

use Playwright\Exception\RuntimeException;
use Playwright\Filesystem\PrivateDirectory;

/** @internal */
final class StorageStateFile
{
    /** @param array<string, mixed> $state */
    public static function save(string $path, array $state): void
    {
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $parent = dirname($path);

        if (!is_dir($parent) && !mkdir($parent, 0700, true) && !is_dir($parent)) {
            throw new RuntimeException(sprintf('Failed to create directory: %s', $parent));
        }

        $directory = PrivateDirectory::create($parent);
        $temporary = $directory.DIRECTORY_SEPARATOR.'state.json';

        try {
            self::write($temporary, $json);

            if (!rename($temporary, $path)) {
                throw new RuntimeException(sprintf('Failed to save storage state file: %s', $path));
            }
        } finally {
            if (file_exists($temporary)) {
                unlink($temporary);
            }

            rmdir($directory);
        }
    }

    private static function write(string $path, string $json): void
    {
        $stream = fopen($path, 'xb');

        if (false === $stream) {
            throw new RuntimeException('Failed to create storage state file.');
        }

        try {
            if (!chmod($path, 0600) || strlen($json) !== fwrite($stream, $json)) {
                throw new RuntimeException('Failed to write private storage state file.');
            }
        } finally {
            fclose($stream);
        }
    }
}
