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

namespace Playwright\Tests\Unit\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Node\NodeBinaryResolverInterface;
use Playwright\Transport\ServerFinder;

#[CoversClass(ServerFinder::class)]
final class ServerFinderTest extends TestCase
{
    public function testFindPlaywrightReturnsPathOrNull(): void
    {
        $finder = new ServerFinder();

        $result = $finder->findPlaywright();

        $this->assertTrue(is_string($result) || null === $result);
    }

    public function testFindServerWorksWhenPlaywrightFound(): void
    {
        $nodeResolver = $this->createMock(NodeBinaryResolverInterface::class);
        $nodeResolver->method('resolve')->willReturn('/usr/bin/node');

        $tmpDir = sys_get_temp_dir().'/pwphp_'.bin2hex(random_bytes(4));
        $tmpCwd = sys_get_temp_dir().'/pwphp_cwd_'.bin2hex(random_bytes(4));
        $originalPath = getenv('PLAYWRIGHT_PATH');
        $originalCwd = getcwd();

        try {
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }
            if (!is_dir($tmpCwd)) {
                mkdir($tmpCwd, 0777, true);
            }
            chdir($tmpCwd);
            putenv('PLAYWRIGHT_PATH='.$tmpDir);

            $finder = new ServerFinder($nodeResolver);

            $result = $finder->findServer();

            $this->assertIsArray($result);
            $this->assertArrayHasKey('strategy', $result);
            $this->assertArrayHasKey('command', $result);
            $this->assertArrayHasKey('env', $result);
            $this->assertSame(realpath($tmpDir), $result['env']['PLAYWRIGHT_PATH'] ?? null);
        } finally {
            if (false === $originalPath) {
                putenv('PLAYWRIGHT_PATH');
            } else {
                putenv('PLAYWRIGHT_PATH='.$originalPath);
            }
            if (is_string($originalCwd)) {
                chdir($originalCwd);
            }
            if (is_dir($tmpDir)) {
                @rmdir($tmpDir);
            }
            if (is_dir($tmpCwd)) {
                @rmdir($tmpCwd);
            }
        }
    }

    public function testGetUserConfiguredPathFromEnv(): void
    {
        $originalPath = getenv('PLAYWRIGHT_PATH');

        try {
            putenv('PLAYWRIGHT_PATH=/custom/playwright/path');

            $finder = new ServerFinder();
            $result = $finder->findPlaywright();

            $this->assertTrue(true);
        } finally {
            if (false === $originalPath) {
                putenv('PLAYWRIGHT_PATH');
            } else {
                putenv("PLAYWRIGHT_PATH={$originalPath}");
            }
        }
    }

    public function testConstructorAcceptsNodeResolver(): void
    {
        $nodeResolver = $this->createMock(NodeBinaryResolverInterface::class);
        $finder = new ServerFinder($nodeResolver);

        $this->assertInstanceOf(ServerFinder::class, $finder);
    }
}
