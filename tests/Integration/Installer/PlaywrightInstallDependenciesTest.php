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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

#[CoversNothing]
final class PlaywrightInstallDependenciesTest extends TestCase
{
    private string $serverDir;

    private string $stubBinDir;

    protected function setUp(): void
    {
        $projectDir = \dirname(__DIR__, 3);

        $sandbox = sys_get_temp_dir().'/playwright-install-'.bin2hex(random_bytes(6));
        mkdir($sandbox, 0o777, true);
        $sandbox = realpath($sandbox);

        $this->serverDir = $sandbox.'/bin';
        $this->stubBinDir = $sandbox.'/stub-bin';
        mkdir($this->serverDir);
        mkdir($this->stubBinDir);

        copy($projectDir.'/bin/playwright-install', $this->serverDir.'/playwright-install');
        copy($projectDir.'/bin/package.json', $this->serverDir.'/package.json');
        symlink($projectDir.'/vendor', $this->serverDir.'/vendor');

        $this->stubCommand('node', 'v20.0.0');
        $this->stubCommand('npm', '10.0.0');
        $this->stubCommand('pnpm', '10.0.0');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(\dirname($this->serverDir));
    }

    #[Test]
    public function itIsolatesThePnpmInstallFromAnOuterWorkspace(): void
    {
        $process = $this->runInstaller();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString(
            "'pnpm' 'install' '--prod' '--no-frozen-lockfile' '--ignore-workspace' '--lockfile-dir' '{$this->serverDir}'",
            $process->getOutput(),
        );
    }

    #[Test]
    public function itIsolatesThePnpmInstallFromAnOuterWorkspaceWhenALockfileExists(): void
    {
        touch($this->serverDir.'/pnpm-lock.yaml');

        $process = $this->runInstaller();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString(
            "'pnpm' 'install' '--prod' '--frozen-lockfile' '--ignore-workspace' '--lockfile-dir' '{$this->serverDir}'",
            $process->getOutput(),
        );
    }

    private function runInstaller(string ...$arguments): Process
    {
        $process = new Process(
            [\PHP_BINARY, $this->serverDir.'/playwright-install', '--dry-run', '--verbose', ...$arguments],
            env: ['PATH' => $this->stubBinDir.\PATH_SEPARATOR.getenv('PATH')],
        );
        $process->run();

        return $process;
    }

    private function stubCommand(string $name, string $version): void
    {
        $path = $this->stubBinDir.'/'.$name;

        file_put_contents($path, "#!/bin/sh\necho {$version}\n");
        \chmod($path, 0o755);
    }

    private function removeDirectory(string $directory): void
    {
        foreach (scandir($directory) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $directory.'/'.$entry;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);

                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
