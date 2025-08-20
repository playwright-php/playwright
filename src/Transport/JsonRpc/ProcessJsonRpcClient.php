<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport\JsonRpc;

use PlaywrightPHP\Exception\DisconnectedException;
use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Exception\TimeoutException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Process\Process;

/**
 * JSON-RPC client that communicates with a Playwright process via stdin/stdout.
 *
 * @experimental
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ProcessJsonRpcClient extends JsonRpcClient
{
    private string $outputBuffer = '';

    /** @var array<int, array{resolve: callable, reject: callable, method: string}> */
    private array $pendingRequests = [];

    public function __construct(
        private readonly Process $process,
        private readonly ProcessLauncherInterface $processLauncher,
        ?ClockInterface $clock = null,
        ?LoggerInterface $logger = null,
        float $defaultTimeoutMs = 30000.0,
    ) {
        parent::__construct(
            clock: $clock ?? new Clock(),
            logger: $logger ?? new NullLogger(),
            defaultTimeoutMs: $defaultTimeoutMs
        );
    }

    protected function sendAndReceive(array $request, ?float $deadline): array
    {
        $this->ensureProcessRunning();

        $requestId = $request['id'];
        $json = json_encode($request, JSON_THROW_ON_ERROR);

        $this->logger->debug('Sending JSON-RPC request to process', [
            'json' => $json,
            'pid' => $this->process->getPid(),
        ]);

        try {
            $this->process->getInputStream()->write($json."\n");
        } catch (\Throwable $e) {
            throw new NetworkException('Failed to write to process stdin: '.$e->getMessage(), 0, $e);
        }

        return $this->waitForResponse($requestId, $deadline);
    }

    private function waitForResponse(int $requestId, ?float $deadline): array
    {
        $pollInterval = 1000; // 1ms
        $maxInterval = 10000; // 10ms

        while (true) {
            if (null !== $deadline && $this->getCurrentTimeMs() > $deadline) {
                throw new TimeoutException(sprintf('JSON-RPC request %d timed out', $requestId), $deadline - $this->getCurrentTimeMs());
            }

            $this->ensureProcessRunning();
            $this->readProcessOutput();

            $response = $this->findResponseForRequest($requestId);
            if (null !== $response) {
                return $response;
            }

            usleep((int) $pollInterval);
            $pollInterval = min($pollInterval * 1.1, $maxInterval);
        }
    }

    private function readProcessOutput(): void
    {
        $stdout = $this->process->getIncrementalOutput();
        if ('' !== $stdout) {
            $this->outputBuffer .= $stdout;
        }

        $stderr = $this->process->getIncrementalErrorOutput();
        if ('' !== $stderr) {
            $this->logger->warning('Process stderr', ['stderr' => $stderr]);
        }
    }

    private function findResponseForRequest(int $requestId): ?array
    {
        while (($pos = strpos($this->outputBuffer, "\n")) !== false) {
            $line = substr($this->outputBuffer, 0, $pos);
            $this->outputBuffer = substr($this->outputBuffer, $pos + 1);

            $line = trim($line);
            if ('' === $line) {
                continue;
            }

            if ('READY' === $line) {
                $this->logger->debug('Process signaled ready');
                continue;
            }

            try {
                $data = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($data)) {
                    $this->logger->warning('Received non-array JSON from process', ['line' => $line]);
                    continue;
                }
                if (isset($data['id']) && $data['id'] === $requestId) {
                    $this->logger->debug('Received JSON-RPC response', [
                        'id' => $requestId,
                        'hasError' => isset($data['error']),
                        'hasResult' => isset($data['result']),
                    ]);

                    return $data;
                }
                if (isset($data['event'])) {
                    $this->logger->debug('Received event from process', ['event' => $data['event']]);
                }
            } catch (\JsonException $e) {
                $this->logger->warning('Failed to parse JSON from process', [
                    'line' => $line,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return null;
    }

    private function ensureProcessRunning(): void
    {
        if (!$this->process->isRunning()) {
            $exitCode = $this->process->getExitCode() ?? -1;
            throw new DisconnectedException(sprintf('Process exited with code %d', $exitCode), 0, null, ['exitCode' => $exitCode, 'pid' => $this->process->getPid()]);
        }

        try {
            $this->processLauncher->ensureRunning($this->process, 'JSON-RPC communication');
        } catch (\Throwable $e) {
            throw new DisconnectedException('Process health check failed: '.$e->getMessage(), 0, $e);
        }
    }
}
