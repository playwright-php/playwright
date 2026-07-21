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

namespace Playwright\Tests\Integration\Transport;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Process;

/**
 * A killed PHP process must not leave the Node bridge and its browser behind.
 *
 * SIGKILL is the case that matters: no `register_shutdown_function` runs, so
 * nothing on the PHP side can clean up. The only thing standing between a killed
 * test run and a browser that outlives it for days is the bridge noticing that
 * its stdin closed. That behaviour lives in `bin/playwright-server.js`, which is
 * why this test covers no PHP class.
 *
 * Reading parentage and force-killing differ per platform: Linux has `/proc`,
 * macOS shells out to `ps`, Windows queries WMI and kills with `taskkill`. Each
 * variant is gated so only the applicable one runs.
 *
 * CI runs Linux only. The Windows variant therefore fails on a developer machine
 * or nowhere, which is why it asserts its way to a loud failure rather than
 * returning empty results that would read as a pass: an inspector that cannot
 * see the process tree trips `assertNotSame([], ...)` before anything is killed.
 */
#[CoversNothing]
#[Group('integration')]
final class OrphanedBrowserTest extends TestCase
{
    private const READY_TIMEOUT_SECONDS = 60;
    private const DEATH_TIMEOUT_SECONDS = 20;

    private ?Process $probe = null;
    private ?string $readyFile = null;

    /** @var list<int> */
    private array $watchedPids = [];

    protected function tearDown(): void
    {
        // A failed assertion must not leak the very orphans this test is about.
        foreach ($this->watchedPids as $pid) {
            if ('Windows' === \PHP_OS_FAMILY) {
                $this->killWithTaskkill($pid);
            } elseif (\function_exists('posix_kill')) {
                @posix_kill($pid, 9);
            }
        }

        $this->probe?->stop(0);
        $this->probe = null;
        $this->watchedPids = [];

        if (null !== $this->readyFile && file_exists($this->readyFile)) {
            @unlink($this->readyFile);
        }
        $this->readyFile = null;

        parent::tearDown();
    }

    #[RequiresOperatingSystemFamily('Darwin')]
    #[RequiresPhpExtension('posix')]
    public function testBridgeAndBrowserDieWithAKilledPhpProcessOnMacOs(): void
    {
        $this->assertProcessTreeDiesWithItsParent(
            $this->childrenFromPs(...),
            $this->commandLineFromPs(...),
            $this->killWithSigkill(...),
        );
    }

    #[RequiresOperatingSystemFamily('Linux')]
    #[RequiresPhpExtension('posix')]
    public function testBridgeAndBrowserDieWithAKilledPhpProcessOnLinux(): void
    {
        $this->assertProcessTreeDiesWithItsParent(
            $this->childrenFromProc(...),
            $this->commandLineFromProc(...),
            $this->killWithSigkill(...),
        );
    }

    #[RequiresOperatingSystemFamily('Windows')]
    public function testBridgeAndBrowserDieWithAKilledPhpProcessOnWindows(): void
    {
        $this->assertProcessTreeDiesWithItsParent(
            $this->childrenFromWmi(...),
            $this->commandLineFromWmi(...),
            $this->killWithTaskkill(...),
        );
    }

    /**
     * @param callable(int): list<int> $children    resolves the direct children of a pid
     * @param callable(int): ?string   $commandLine resolves a pid's command line, null when it is gone
     * @param callable(int): void      $forceKill   kills a pid outright, no chance to clean up
     */
    private function assertProcessTreeDiesWithItsParent(callable $children, callable $commandLine, callable $forceKill): void
    {
        $phpPid = $this->startProbe();

        $bridgePids = $children($phpPid);
        self::assertNotSame([], $bridgePids, 'the probe should have spawned a Node bridge');

        $browserPids = [];
        foreach ($bridgePids as $bridgePid) {
            $browserPids = [...$browserPids, ...$children($bridgePid)];
        }
        self::assertNotSame([], $browserPids, 'the Node bridge should have spawned a browser');

        // Remember each command line: a pid alone would give false positives once
        // the OS recycles it, and this test polls for several seconds.
        $watched = [];
        foreach ([...$bridgePids, ...$browserPids] as $pid) {
            $watched[$pid] = $commandLine($pid);
        }
        $this->watchedPids = array_keys($watched);

        $forceKill($phpPid);

        try {
            $this->probe?->wait();
        } catch (ProcessSignaledException) {
            // Being signaled is the whole point of this test.
        }

        $survivors = $this->waitForDeath($watched, $commandLine);

        self::assertSame([], $survivors, sprintf(
            'killing the PHP process left %d orphan(s) behind: %s',
            \count($survivors),
            implode(', ', array_map(
                static fn (int $pid): string => $pid.' ('.($watched[$pid] ?? '?').')',
                $survivors,
            )),
        ));
    }

    /**
     * Launches the probe and returns its pid once the browser is actually up.
     */
    private function startProbe(): int
    {
        $this->readyFile = sys_get_temp_dir().'/playwright-php-orphan-'.bin2hex(random_bytes(8));

        $probe = new Process([\PHP_BINARY, __DIR__.'/../../Fixtures/orphan-probe.php', $this->readyFile]);
        $probe->setTimeout(null);
        $probe->start();
        $this->probe = $probe;

        $pid = $probe->getPid();
        if (null === $pid) {
            self::markTestSkipped('could not start the probe process');
        }

        $deadline = microtime(true) + self::READY_TIMEOUT_SECONDS;
        while (!file_exists($this->readyFile)) {
            if (!$probe->isRunning()) {
                self::markTestSkipped('probe could not launch a browser (missing Node or browser binaries): '.trim($probe->getErrorOutput()));
            }
            if (microtime(true) > $deadline) {
                self::markTestSkipped(sprintf('probe did not report ready within %ds', self::READY_TIMEOUT_SECONDS));
            }
            usleep(200_000);
        }

        return $pid;
    }

    /**
     * @param array<int, ?string>    $watched     pid to the command line it had when captured
     * @param callable(int): ?string $commandLine
     *
     * @return list<int> pids still alive when the deadline expired
     */
    private function waitForDeath(array $watched, callable $commandLine): array
    {
        $deadline = microtime(true) + self::DEATH_TIMEOUT_SECONDS;

        do {
            $survivors = [];
            foreach ($watched as $pid => $capturedCommandLine) {
                if ($commandLine($pid) === $capturedCommandLine) {
                    $survivors[] = $pid;
                }
            }

            if ([] === $survivors) {
                return [];
            }

            usleep(250_000);
        } while (microtime(true) < $deadline);

        return $survivors;
    }

    /**
     * macOS has no /proc, so the whole table has to come from `ps`.
     *
     * @return list<int>
     */
    private function childrenFromPs(int $parentPid): array
    {
        $ps = new Process(['ps', '-eo', 'pid=,ppid=']);
        $ps->run();

        $children = [];
        foreach (explode("\n", $ps->getOutput()) as $line) {
            $fields = preg_split('/\s+/', trim($line), -1, \PREG_SPLIT_NO_EMPTY);
            if (!\is_array($fields) || 2 !== \count($fields)) {
                continue;
            }
            if ((int) $fields[1] === $parentPid) {
                $children[] = (int) $fields[0];
            }
        }

        return $children;
    }

    private function commandLineFromPs(int $pid): ?string
    {
        $ps = new Process(['ps', '-p', (string) $pid, '-o', 'command=']);
        $ps->run();

        $commandLine = trim($ps->getOutput());

        return '' === $commandLine ? null : $commandLine;
    }

    /**
     * Linux exposes parentage in /proc, no subprocess needed.
     *
     * @return list<int>
     */
    private function childrenFromProc(int $parentPid): array
    {
        $entries = glob('/proc/[0-9]*');
        if (false === $entries) {
            return [];
        }

        $children = [];
        foreach ($entries as $entry) {
            $status = @file_get_contents($entry.'/status');
            if (false === $status) {
                continue;
            }
            if (1 === preg_match('/^PPid:\s+(\d+)$/m', $status, $matches) && (int) $matches[1] === $parentPid) {
                $children[] = (int) basename($entry);
            }
        }

        return $children;
    }

    private function commandLineFromProc(int $pid): ?string
    {
        $raw = @file_get_contents('/proc/'.$pid.'/cmdline');
        if (false === $raw || '' === $raw) {
            return null;
        }

        return trim(str_replace("\0", ' ', $raw));
    }

    /**
     * Windows has neither /proc nor ps; parentage lives in WMI.
     *
     * A PowerShell that will not run is an unusable environment, not a passing
     * test: skip loudly instead of reporting an empty tree, which the caller
     * would otherwise have to tell apart from a genuinely childless process.
     *
     * @return list<int>
     */
    private function childrenFromWmi(int $parentPid): array
    {
        $output = $this->runPowerShell(sprintf(
            'Get-CimInstance Win32_Process -Filter "ParentProcessId=%d" | ForEach-Object { $_.ProcessId }',
            $parentPid,
        ));

        $children = [];
        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if ('' !== $line && ctype_digit($line)) {
                $children[] = (int) $line;
            }
        }

        return $children;
    }

    private function commandLineFromWmi(int $pid): ?string
    {
        $commandLine = trim($this->runPowerShell(sprintf(
            '(Get-CimInstance Win32_Process -Filter "ProcessId=%d").CommandLine',
            $pid,
        )));

        return '' === $commandLine ? null : $commandLine;
    }

    private function runPowerShell(string $command): string
    {
        $powerShell = new Process(['powershell', '-NoProfile', '-NonInteractive', '-Command', $command]);
        $powerShell->run();

        if (!$powerShell->isSuccessful()) {
            self::markTestSkipped('cannot inspect the process tree, PowerShell failed: '.trim($powerShell->getErrorOutput()));
        }

        return $powerShell->getOutput();
    }

    private function killWithSigkill(int $pid): void
    {
        posix_kill($pid, 9);
    }

    /**
     * No /T here, deliberately: killing the tree would take the bridge and the
     * browser down by hand and prove nothing. Only the PHP process dies.
     */
    private function killWithTaskkill(int $pid): void
    {
        (new Process(['taskkill', '/F', '/PID', (string) $pid]))->run();
    }
}
