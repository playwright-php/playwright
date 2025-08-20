<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport\JsonRpc;

use PlaywrightPHP\Exception\ProcessCrashedException;
use PlaywrightPHP\Exception\ProcessLaunchException;
use Symfony\Component\Process\Process;

/**
 * Interface for process launchers that manage Node.js bridge processes.
 *
 * @experimental
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface ProcessLauncherInterface
{
    /**
     * @param list<string>          $command
     * @param array<string, string> $env
     *
     * @throws ProcessLaunchException
     */
    public function start(
        array $command,
        ?string $cwd = null,
        array $env = [],
        ?float $timeout = null,
    ): Process;

    /**
     * @throws ProcessCrashedException
     */
    public function ensureRunning(Process $process, string $phase = 'operation'): void;

    public function getStderrOutput(): string;

    public function clearStderrBuffer(): void;

    public function terminate(Process $process, float $gracePeriodSeconds = 2.0): void;

    /**
     * @throws ProcessCrashedException
     */
    public function waitForExit(Process $process, ?float $timeoutSeconds = null): int;
}
