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
final class PlaywrightInstallCliTest extends TestCase
{
    #[Test]
    public function itForwardsSelectedManagedBrowserTargetsToPlaywright(): void
    {
        $process = $this->runInstaller('--dry-run', '--verbose', 'firefox');

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString("'playwright' 'install' 'firefox'", $process->getOutput());
    }

    #[Test]
    public function itForwardsBrowserTargetsAfterTheSystemDependenciesOption(): void
    {
        $process = $this->runInstaller('--dry-run', '--verbose', '--with-deps', 'chromium', 'firefox');

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString("'playwright' 'install' '--with-deps' 'chromium' 'firefox'", $process->getOutput());
    }

    #[Test]
    public function itKeepsTheDefaultBrowserInstallShortcut(): void
    {
        $process = $this->runInstaller('--dry-run', '--verbose', '--browsers');

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString("'playwright' 'install'", $process->getOutput());
        $this->assertStringNotContainsString("'playwright' 'install' 'firefox'", $process->getOutput());
    }

    #[Test]
    public function itForwardsBrandedBrowserTargetsWithoutTreatingThemAsAliases(): void
    {
        $process = $this->runInstaller('--dry-run', '--verbose', 'chrome', 'msedge-beta');

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
        $process = $this->runInstaller('safari');

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
        $process = $this->runInstaller('--browsers', 'chromium');

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
        $process = $this->runInstaller('--help');

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString('Managed browser targets:', $process->getOutput());
        $this->assertStringContainsString("chromium          Playwright's bundled Chromium browser", $process->getOutput());
        $this->assertStringContainsString("firefox           Playwright's Firefox browser", $process->getOutput());
        $this->assertStringContainsString("webkit            Playwright's WebKit browser", $process->getOutput());
        $this->assertStringContainsString('Branded browser targets:', $process->getOutput());
        $this->assertStringContainsString('chrome            Google Chrome stable', $process->getOutput());
        $this->assertStringContainsString('msedge-beta       Microsoft Edge Beta', $process->getOutput());
    }

    private function runInstaller(string ...$arguments): Process
    {
        $process = new Process([
            \PHP_BINARY,
            dirname(__DIR__, 3).'/bin/playwright-install',
            ...$arguments,
        ]);
        $process->run();

        return $process;
    }
}
