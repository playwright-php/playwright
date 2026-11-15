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

namespace Playwright\Filesystem;

use Playwright\Exception\RuntimeException;

/** @internal */
final class PrivateDirectory
{
    private static ?string $artifactDirectory = null;

    public static function create(string $parent): string
    {
        $directory = rtrim($parent, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'playwright-'.bin2hex(random_bytes(16));

        if (!mkdir($directory, 0700) || !chmod($directory, 0700)) {
            throw new RuntimeException('Failed to create a private Playwright directory.');
        }

        return $directory;
    }

    public static function artifacts(): string
    {
        return self::$artifactDirectory ??= self::create(sys_get_temp_dir());
    }
}
