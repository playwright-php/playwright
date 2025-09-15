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
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * JSON-RPC client that communicates with a Playwright process via stdin/stdout.
 *
 * @experimental
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ProcessJsonRpcClient extends JsonRpcClient implements JsonRpcClientInterface
{
    private string $outputBuffer = '';
    private ?InputStream $inputStream = null;

    /** @var array<int, array<string, mixed>> */
    private array $responses = [];

    private ?\Closure $eventHandler = null;

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

        $this->inputStream = $this->processLauncher->getInputStream();
        if (!$this->inputStream instanceof InputStream) {
            throw new NetworkException('ProcessLauncher must have an InputStream for JSON-RPC communication');
        }
    }

    public function setEventHandler(?\Closure $eventHandler): void
    {
        $this->eventHandler = $eventHandler;
    }

    protected function sendAndReceive(array $request, ?float $deadline): array
    {
        $this->ensureProcessRunning();

        $requestId = $request['id'] ?? $request['requestId'] ?? null;
        if (null === $requestId) {
            throw new NetworkException('Request must have either id or requestId');
        }
        if (!is_int($requestId) && !is_string($requestId)) {
            throw new NetworkException('Request ID must be an integer or string');
        }

        $json = json_encode($request, JSON_THROW_ON_ERROR);
        $framedMessage = LspFraming::encode($json);

        $this->logger->debug('Sending JSON-RPC request to process', [
            'json' => $json,
            'pid' => $this->process->getPid(),
        ]);

        if (null === $this->inputStream) {
            throw new NetworkException('Input stream is not available');
        }

        try {
            $this->inputStream->write($framedMessage);
        } catch (\Throwable $e) {
            throw new NetworkException('Failed to write to process stdin: '.$e->getMessage(), 0, $e);
        }

        return $this->waitForResponse($requestId, $deadline);
    }

    /**
     * @return array<string, mixed>
     */
    private function waitForResponse(int|string $requestId, ?float $deadline): array
    {
        $pollInterval = 1000;
        $maxInterval = 10000;

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

        $this->processAndDispatchMessages();
    }

    private function processAndDispatchMessages(): void
    {
        $decoded = LspFraming::decode($this->outputBuffer);

        foreach ($decoded['messages'] as $messageContent) {
            $this->dispatchMessage($messageContent);
        }

        $this->outputBuffer = $decoded['remainingBuffer'];
    }

    private function dispatchMessage(string $messageContent): void
    {
        $messageContent = trim($messageContent);
        if ('' === $messageContent) {
            return;
        }

        try {
            $data = json_decode($messageContent, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                if ('READY' === $messageContent) {
                    $this->logger->debug('Process signaled ready');

                    return;
                }
                $this->logger->warning('Received non-array JSON from process', ['message' => $messageContent]);

                return;
            }

            if (isset($data['type']) && 'ready' === $data['type']) {
                $this->logger->debug('Process signaled ready', ['message' => $data['message'] ?? 'READY']);

                return;
            }

            if (isset($data['id'])) {
                $this->logger->debug('Received JSON-RPC response', [
                    'id' => $data['id'],
                    'hasError' => isset($data['error']),
                    'hasResult' => isset($data['result']),
                ]);
                $this->responses[$data['id']] = $data;
            } elseif (isset($data['requestId'])) {
                $this->logger->debug('Received raw response', [
                    'requestId' => $data['requestId'],
                    'hasError' => isset($data['error']),
                ]);
                $this->responses[$data['requestId']] = $data;
            }

            if (isset($data['event'])) {
                $this->logger->debug('Received event from process', ['event' => $data['event']]);
                if ($this->eventHandler) {
                    ($this->eventHandler)($data);
                }
            }
        } catch (\JsonException $e) {
            if ('READY' === $messageContent) {
                $this->logger->debug('Process signaled ready');

                return;
            }
            $this->logger->warning('Failed to parse JSON from process', [
                'message' => $messageContent,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findResponseForRequest(int|string $requestId): ?array
    {
        if (isset($this->responses[$requestId])) {
            $response = $this->responses[$requestId];
            unset($this->responses[$requestId]);

            return $response;
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
