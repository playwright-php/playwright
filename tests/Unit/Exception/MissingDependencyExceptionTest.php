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

namespace Playwright\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\MissingDependencyException;
use Playwright\Exception\RuntimeException;

#[CoversClass(MissingDependencyException::class)]
final class MissingDependencyExceptionTest extends TestCase
{
    public function testServerDependenciesNamesTheInstallCommand(): void
    {
        $exception = MissingDependencyException::server();

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertStringContainsString(MissingDependencyException::INSTALL_COMMAND, $exception->getMessage());
    }

    public function testServerDependenciesKeepsTheUnderlyingDetails(): void
    {
        $exception = MissingDependencyException::server("Error: Cannot find module 'playwright'");

        $this->assertStringContainsString(MissingDependencyException::INSTALL_COMMAND, $exception->getMessage());
        $this->assertStringContainsString("Cannot find module 'playwright'", $exception->getMessage());
    }

    public function testBrowsersNamesTheInstallCommandAndKeepsTheDetails(): void
    {
        $exception = MissingDependencyException::browsers("browserType.launch: Executable doesn't exist at /tmp/chrome");

        $this->assertStringContainsString(MissingDependencyException::INSTALL_COMMAND, $exception->getMessage());
        $this->assertStringContainsString("Executable doesn't exist at /tmp/chrome", $exception->getMessage());
    }

    public function testHostDependenciesNamesTheWithDepsCommandAndKeepsTheDetails(): void
    {
        $exception = MissingDependencyException::hostLibraries('Host system is missing dependencies to run browsers.');

        $this->assertStringContainsString(MissingDependencyException::WITH_DEPENDENCIES_COMMAND, $exception->getMessage());
        $this->assertStringContainsString('Host system is missing dependencies', $exception->getMessage());
    }
}
