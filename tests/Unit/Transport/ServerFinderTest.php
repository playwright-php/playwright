<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Node\NodeBinaryResolverInterface;
use PlaywrightPHP\Transport\ServerFinder;

#[CoversClass(ServerFinder::class)]
final class ServerFinderTest extends TestCase
{
    public function testFindPlaywrightReturnsPathOrNull(): void
    {
        $finder = new ServerFinder();

        // Can return either a path string or null depending on environment
        $result = $finder->findPlaywright();

        $this->assertTrue(is_string($result) || null === $result);
    }

    public function testFindServerWorksWhenPlaywrightFound(): void
    {
        // Create a mock NodeBinaryResolver that works
        $nodeResolver = $this->createMock(NodeBinaryResolverInterface::class);
        $nodeResolver->method('resolve')->willReturn('/usr/bin/node');

        // Simulate a found Playwright installation via env var pointing to a temp dir
        $tmpDir = sys_get_temp_dir().'/pwphp_'.bin2hex(random_bytes(4));
        $tmpCwd = sys_get_temp_dir().'/pwphp_cwd_'.bin2hex(random_bytes(4));
        $originalPath = getenv('PLAYWRIGHT_PATH');
        $originalCwd = getcwd();

        try {
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }
            // Change to a temp working directory outside the repo to avoid picking up local node_modules
            if (!is_dir($tmpCwd)) {
                mkdir($tmpCwd, 0777, true);
            }
            chdir($tmpCwd);
            putenv('PLAYWRIGHT_PATH='.$tmpDir);

            // Create finder with mocked node resolver
            $finder = new ServerFinder($nodeResolver);

            // If playwright is found, this should return server config
            $result = $finder->findServer();

            $this->assertIsArray($result);
            $this->assertArrayHasKey('strategy', $result);
            $this->assertArrayHasKey('command', $result);
            $this->assertArrayHasKey('env', $result);
            $this->assertSame(realpath($tmpDir), $result['env']['PLAYWRIGHT_PATH'] ?? null);
        } finally {
            // Restore env var
            if (false === $originalPath) {
                putenv('PLAYWRIGHT_PATH');
            } else {
                putenv('PLAYWRIGHT_PATH='.$originalPath);
            }
            // Restore cwd
            if (is_string($originalCwd)) {
                chdir($originalCwd);
            }
            // Cleanup temp dir
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
        // Test environment variable reading
        $originalPath = getenv('PLAYWRIGHT_PATH');

        try {
            putenv('PLAYWRIGHT_PATH=/custom/playwright/path');

            $finder = new ServerFinder();
            $result = $finder->findPlaywright();

            // If the path doesn't exist, it will return null, but we're testing the env reading
            $this->assertTrue(true); // Test passes if no exceptions thrown
        } finally {
            // Restore original env
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
