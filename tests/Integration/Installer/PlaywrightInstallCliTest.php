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

namespace Playwright\Tests\Integration\Installer;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

#[CoversNothing]
final class PlaywrightInstallCliTest extends TestCase
{
    /**
     * @var array<string, string>
     */
    private array $explicitExecutables = [];

    private bool $useSymlinks = false;

    #[Test]
    public function itForwardsSelectedManagedBrowserTargetsToPlaywright(): void
    {
        $process = $this->runInstaller(['--dry-run', '--verbose', 'firefox']);

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString("'playwright' 'install' 'firefox'", $process->getOutput());
    }

    #[Test]
    public function itForwardsBrowserTargetsAfterTheSystemDependenciesOption(): void
    {
        $process = $this->runInstaller(['--dry-run', '--verbose', '--with-deps', 'chromium', 'firefox']);

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString("'playwright' 'install' '--with-deps' 'chromium' 'firefox'", $process->getOutput());
    }

    #[Test]
    public function itKeepsTheDefaultBrowserInstallShortcut(): void
    {
        $process = $this->runInstaller(['--dry-run', '--verbose', '--browsers']);

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString("'yarn' 'playwright' 'install'", $process->getOutput());
        $this->assertStringNotContainsString("'playwright' 'install' 'firefox'", $process->getOutput());
    }

    #[Test]
    public function itForwardsBrandedBrowserTargetsWithoutTreatingThemAsAliases(): void
    {
        $process = $this->runInstaller(['--dry-run', '--verbose', 'chrome', 'msedge-beta']);

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString("'playwright' 'install' 'chrome' 'msedge-beta'", $process->getOutput());
        $this->assertStringContainsString(
            "Warning: branded browsers are installed in the operating system's global location.",
            $process->getOutput(),
        );
    }

    #[Test]
    public function itRejectsUnknownBrowserTargetsWithoutNormalizingAliases(): void
    {
        $process = $this->runInstaller(['safari']);

        $this->assertSame(2, $process->getExitCode());
        $this->assertSame('', $process->getOutput());
        $this->assertSame(
            "Unknown browser target: safari. Supported browsers: chromium, firefox, webkit, chrome, chrome-beta, msedge, msedge-beta.\n",
            $process->getErrorOutput(),
        );
    }

    #[Test]
    public function itRejectsTheDefaultShortcutCombinedWithExplicitTargets(): void
    {
        $process = $this->runInstaller(['--browsers', 'chromium']);

        $this->assertSame(2, $process->getExitCode());
        $this->assertSame('', $process->getOutput());
        $this->assertSame(
            "The --browsers option cannot be combined with explicit browser targets.\n",
            $process->getErrorOutput(),
        );
    }

    #[Test]
    public function itListsTheSupportedBrowserTargetsInHelp(): void
    {
        $process = $this->runInstaller(['--help']);

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString('Managed browser targets:', $process->getOutput());
        $this->assertStringContainsString("chromium          Playwright's bundled Chromium browser", $process->getOutput());
        $this->assertStringContainsString("firefox           Playwright's Firefox browser", $process->getOutput());
        $this->assertStringContainsString("webkit            Playwright's WebKit browser", $process->getOutput());
        $this->assertStringContainsString('Branded browser targets:', $process->getOutput());
        $this->assertStringContainsString('chrome            Google Chrome stable', $process->getOutput());
        $this->assertStringContainsString('msedge-beta       Microsoft Edge Beta', $process->getOutput());
    }

    #[Test]
    public function itHonorsNpmDeclaredByTheParentProjectWhenYarnIsAvailable(): void
    {
        $process = $this->runInstaller(['--dry-run', '--verbose', '--browsers'], 'npm@11.6.0');

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString("'npm' '--version'", $process->getOutput());
        $this->assertStringContainsString("'npx' 'playwright' 'install'", $process->getOutput());
        $this->assertStringNotContainsString("'yarn'", $process->getOutput());
    }

    #[Test]
    #[DataProvider('explicitPackageManagers')]
    public function itUsesTheExplicitBinaryForEveryPackageManagerCommand(string $manager, string $browserCommand): void
    {
        $versions = ['npm' => '10.0.0', 'pnpm' => '10.0.0', 'yarn' => '1.22.22'];
        $this->explicitExecutables = [$manager => $versions[$manager]];
        $process = $this->runInstaller([
            '--verbose', '--browsers', '--package-manager-bin=tools with spaces/'.$manager,
        ], 'npm@11.6.0');

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $prefix = "/tools with spaces/$manager'";
        $this->assertStringContainsString($prefix." '--version'", $process->getOutput());
        $this->assertStringContainsString($prefix." 'install'", $process->getOutput());
        $this->assertStringContainsString($prefix.' '.$browserCommand, $process->getOutput());
        $this->assertStringNotContainsString("'npx'", $process->getOutput());
    }

    public static function explicitPackageManagers(): iterable
    {
        yield 'npm' => ['npm', "'exec' '--' 'playwright' 'install'"];
        yield 'pnpm' => ['pnpm', "'exec' 'playwright' 'install'"];
        yield 'yarn' => ['yarn', "'playwright' 'install'"];
    }

    #[Test]
    public function itPreservesTheExplicitExecutableSymlink(): void
    {
        $this->explicitExecutables = ['pnpm' => '10.0.0'];
        $this->useSymlinks = true;
        $process = $this->runInstaller([
            '--dry-run', '--browsers', '--package-manager-bin', 'tools with spaces/pnpm',
        ]);

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString("/tools with spaces/pnpm' 'exec' 'playwright' 'install'", $process->getOutput());
        $this->assertStringNotContainsString('pnpm-target', $process->getOutput());
    }

    #[Test]
    #[DataProvider('invalidBinaries')]
    public function itRejectsInvalidExplicitBinaries(string $binary, string $message): void
    {
        $process = $this->runInstaller(['--dry-run', '--package-manager-bin='.$binary]);

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString($message, $process->getOutput());
        $this->assertStringNotContainsString('[dry-run] Would execute:', $process->getOutput());
    }

    public static function invalidBinaries(): iterable
    {
        yield 'missing binary' => ['missing/npm', 'was not found or is not executable'];
        yield 'unknown manager' => ['tools/custom-manager', 'must be named npm, yarn, or pnpm'];
    }

    #[Test]
    public function itRequiresAValueForTheBinaryOption(): void
    {
        $process = $this->runInstaller(['--package-manager-bin']);

        $this->assertSame(2, $process->getExitCode());
        $this->assertSame("The --package-manager-bin option requires a value.\n", $process->getErrorOutput());
    }

    /**
     * @param list<string> $arguments
     */
    private function runInstaller(array $arguments, ?string $declaration = null): Process
    {
        $projectDirectory = sys_get_temp_dir().'/playwright-install-'.bin2hex(random_bytes(8));
        $this->createProject($projectDirectory, $declaration);
        $this->createExecutables($projectDirectory.'/bin', ['node' => 'v20.0.0', 'npm' => '11.6.0', 'yarn' => '1.22.22']);
        $this->createExecutables($projectDirectory.'/tools with spaces', $this->explicitExecutables);

        $process = new Process([
            \PHP_BINARY,
            $projectDirectory.'/server/playwright-install',
            ...$arguments,
        ], $projectDirectory, [
            'PATH' => $projectDirectory.'/bin',
        ]);

        try {
            $process->run();
        } finally {
            $this->removeProject($projectDirectory);
        }

        return $process;
    }

    private function createProject(string $directory, ?string $declaration): void
    {
        mkdir($directory.'/server', 0777, true);
        symlink(dirname(__DIR__, 3).'/vendor', $directory.'/vendor');
        foreach (['playwright-install', 'package.json', 'playwright-server.js'] as $file) {
            copy(dirname(__DIR__, 3).'/bin/'.$file, $directory.'/server/'.$file);
        }

        if (null !== $declaration) {
            file_put_contents($directory.'/package.json', json_encode([
                'packageManager' => $declaration,
            ], JSON_THROW_ON_ERROR));
        }
    }

    /**
     * @param array<string, string> $executables
     */
    private function createExecutables(string $directory, array $executables): void
    {
        mkdir($directory);
        foreach ($executables as $command => $version) {
            $path = $directory.'/'.$command;
            $script = "#!/bin/sh\necho '$version'\n";
            if ($this->useSymlinks) {
                file_put_contents($path.'-target', $script);
                chmod($path.'-target', 0755);
                symlink($path.'-target', $path);
            } else {
                file_put_contents($path, $script);
                chmod($path, 0755);
            }
        }
    }

    private function removeProject(string $directory): void
    {
        foreach (new \FilesystemIterator($directory) as $file) {
            if ($file->isDir() && !$file->isLink()) {
                $this->removeProject($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($directory);
    }
}
