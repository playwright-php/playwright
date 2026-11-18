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

namespace Playwright\Tests\Unit\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\StorageStateFile;
use Playwright\Filesystem\PrivateDirectory;

#[CoversClass(StorageStateFile::class)]
#[CoversClass(PrivateDirectory::class)]
final class StorageStateFileTest extends TestCase
{
    public function testExportsArePrivateAndReplaceLinksWithoutChangingTheirTargets(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('POSIX file modes and symlinks are required.');
        }

        $directory = PrivateDirectory::create(sys_get_temp_dir());
        $target = $directory.'/target.json';
        $path = $directory.'/state.json';
        $previousMask = umask(0);
        $state = ['cookies' => [], 'origins' => [], 'futureField' => ['preserved' => true]];

        try {
            file_put_contents($target, 'unchanged');
            symlink($target, $path);
            StorageStateFile::save($path, $state);
            clearstatcache();

            $this->assertFalse(is_link($path));
            $this->assertSame('unchanged', file_get_contents($target));
            $this->assertSame(0600, fileperms($path) & 0777);
            $this->assertSame($state, json_decode((string) file_get_contents($path), true));

            chmod($path, 0666);
            StorageStateFile::save($path, $state);
            clearstatcache();
            $this->assertSame(0600, fileperms($path) & 0777);
        } finally {
            umask($previousMask);
            unlink($path);
            unlink($target);
            rmdir($directory);
        }
    }

    public function testNewParentDirectoriesArePrivate(): void
    {
        $directory = PrivateDirectory::create(sys_get_temp_dir());
        $parent = $directory.'/new';

        try {
            StorageStateFile::save($parent.'/state.json', []);
            $this->assertSame([], json_decode((string) file_get_contents($parent.'/state.json'), true));

            if (DIRECTORY_SEPARATOR !== '\\') {
                $this->assertSame(0700, fileperms($parent) & 0777);
            }
        } finally {
            @unlink($parent.'/state.json');
            @rmdir($parent);
            rmdir($directory);
        }
    }
}
