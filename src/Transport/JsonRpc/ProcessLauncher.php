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
use PlaywrightPHP\Support\RingBuffer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Launches and supervises Node.js bridge processes with enhanced diagnostics.
 *
 * @experimental
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ProcessLauncher implements ProcessLauncherInterface
{
    private RingBuffer $stderrBuf;
    private ?InputStream $lastInputStream = null;

    public function __construct(
        private readonly LoggerInterface $logger = new NullLogger(),
        int $stderrLinesToKeep = 500,
    ) {
        $this->stderrBuf = new RingBuffer($stderrLinesToKeep);
    }

    /**
     * @param array<string>         $command
     * @param array<string, string> $env
     */
    public function start(array $command, ?string $cwd = null, array $env = [], ?float $timeout = null): Process
    {
        if (empty($command)) {
            throw new ProcessLaunchException('Command cannot be empty', 0, null, ['command' => $command, 'cwd' => $cwd]);
        }

        try {
            $this->lastInputStream = new InputStream();
            $process = new Process($command, $cwd, $env, null, $timeout);
            $process->setTimeout($timeout);
            $process->setInput($this->lastInputStream);

            $process->start(function (string $type, string $data): void {
                if (Process::ERR === $type) {
                    $this->stderrBuf->push($data);
                }
            });

            usleep(50_000);

            if (!$process->isRunning() && !$process->isSuccessful()) {
                $excerpt = $this->stderrBuf->toString();
                throw new ProcessLaunchException('Failed to launch Node process', 0, null, ['command' => $command, 'cwd' => $cwd, 'env' => array_keys($env), 'stderr' => $excerpt, 'exitCode' => $process->getExitCode(), 'exitCodeText' => $process->getExitCodeText()]);
            }

            $this->log('Node process started successfully', [
                'pid' => $process->getPid(),
                'command' => $command[0] ?? 'unknown',
                'cwd' => $cwd,
                'timeout' => $timeout,
            ]);

            return $process;
        } catch (\Throwable $e) {
            if ($e instanceof ProcessLaunchException) {
                throw $e;
            }
            throw new ProcessLaunchException('Exception while starting Node process: '.$e->getMessage(), 0, $e, ['command' => $command, 'cwd' => $cwd, 'exceptionType' => get_class($e)]);
        }
    }

    public function ensureRunning(Process $process, string $phase = 'operation'): void
    {
        if (!$process->isRunning()) {
            $exit = $process->getExitCode() ?? -1;
            $excerpt = $this->stderrBuf->toString();

            $this->log('Process not running during phase', [
                'phase' => $phase,
                'pid' => $process->getPid(),
                'exitCode' => $exit,
            ]);

            throw new ProcessCrashedException(sprintf('Node process not running during %s', $phase), $exit, $excerpt, ['pid' => $process->getPid(), 'phase' => $phase, 'exitCodeText' => $process->getExitCodeText(), 'isTerminated' => $process->isTerminated(), 'isSuccessful' => $process->isSuccessful()]);
        }
    }

    public function getStderrOutput(): string
    {
        return $this->stderrBuf->toString();
    }

    public function clearStderrBuffer(): void
    {
        $this->stderrBuf->clear();
    }

    /**
     * Get stderr buffer stats for diagnostics.
     *
     * @return array{lines: int, maxLines: int, isEmpty: bool}
     */
    public function getStderrBufferInfo(): array
    {
        return [
            'lines' => $this->stderrBuf->count(),
            'maxLines' => $this->stderrBuf->getMaxSize(),
            'isEmpty' => $this->stderrBuf->isEmpty(),
        ];
    }

    public function terminate(Process $process, float $gracePeriodSeconds = 2.0): void
    {
        if (!$process->isRunning()) {
            $this->log('Process already terminated', ['pid' => $process->getPid()]);

            return;
        }

        $this->log('Terminating process', [
            'pid' => $process->getPid(),
            'gracePeriod' => $gracePeriodSeconds,
        ]);

        try {
            $process->stop($gracePeriodSeconds);
        } catch (\Throwable $e) {
            $this->log('Error during process termination', ['pid' => $process->getPid(), 'error' => $e->getMessage()]);
        }
    }

    public function waitForExit(Process $process, ?float $timeoutSeconds = null): int
    {
        $this->log('Waiting for process to exit', ['pid' => $process->getPid(), 'timeout' => $timeoutSeconds]);

        if (null !== $timeoutSeconds) {
            $process->setTimeout($timeoutSeconds);
        }

        try {
            $process->wait();
        } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
            $this->log('Process wait timed out', ['pid' => $process->getPid()]);
            throw new ProcessCrashedException('Process wait timed out', -1, $this->stderrBuf->toString(), ['pid' => $process->getPid(), 'timeout' => $timeoutSeconds]);
        }

        $exitCode = $process->getExitCode() ?? -1;
        if (0 !== $exitCode) {
            throw new ProcessCrashedException('Process exited with non-zero code', $exitCode, $this->stderrBuf->toString(), ['pid' => $process->getPid(), 'exitCodeText' => $process->getExitCodeText()]);
        }

        return $exitCode;
    }

    public function getInputStream(): ?InputStream
    {
        return $this->lastInputStream;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(string $message, array $context = []): void
    {
        $this->logger->debug('[ProcessLauncher] '.$message, $context);
    }
}
