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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\TimeoutException;
use Playwright\Transport\JsonRpc\LspFraming;
use Playwright\Transport\JsonRpc\ProcessJsonRpcClient;
use Playwright\Transport\JsonRpc\ProcessLauncher;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

#[CoversClass(ProcessJsonRpcClient::class)]
#[Group('integration')]
final class ServerHeartbeatTest extends TestCase
{
    private const HEARTBEAT_INTERVAL_MS = 100;
    private const IDLE_TIMEOUT_MS = 600;

    private ProcessLauncher $launcher;
    private ?Process $server = null;
    private bool $suspended = false;

    protected function tearDown(): void
    {
        if ($this->suspended) {
            $this->server?->signal(\SIGCONT);
        }
        $this->server?->stop(5);
        $this->server = null;

        parent::tearDown();
    }

    public function testACallThatOutlastsTheIdleTimeoutKeepsWaitingForItsResult(): void
    {
        $client = $this->startServer();
        $launch = $client->sendRaw(['action' => 'launch', 'browser' => 'chromium', 'options' => ['headless' => true]], 0.0);
        $this->assertIsString($launch['defaultContextId'] ?? null, json_encode($launch) ?: '');
        $page = $client->sendRaw(['action' => 'context.newPage', 'contextId' => $launch['defaultContextId'], 'options' => []], 0.0);
        $this->assertIsString($page['pageId'] ?? null, json_encode($page) ?: '');

        // no result for longer than the idle timeout: only heartbeats keep the call alive
        $waitMs = 3 * self::IDLE_TIMEOUT_MS;
        $response = $client->sendRaw(['action' => 'locator.waitFor', 'pageId' => $page['pageId'], 'selector' => '#never', 'options' => ['timeout' => $waitMs]], 0.0);

        $this->assertStringContainsString(\sprintf('Timeout %dms exceeded', $waitMs), json_encode($response) ?: '');
    }

    #[RequiresPhpExtension('pcntl')]
    public function testAStoppedServerEndsTheCallWithATimeoutException(): void
    {
        $client = $this->startServer();
        $client->sendRaw(['action' => 'page.url', 'pageId' => 'none'], 0.0);

        $this->server?->signal(\SIGSTOP);
        $this->suspended = true;

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('The Playwright server stopped responding');

        $client->sendRaw(['action' => 'page.url', 'pageId' => 'none'], 0.0);
    }

    public function testAnIdleServerSendsNoHeartbeat(): void
    {
        $client = $this->startServer();
        $client->sendRaw(['action' => 'page.url', 'pageId' => 'none'], 0.0);

        usleep(5 * self::HEARTBEAT_INTERVAL_MS * 1000);

        $this->assertSame('', $this->server?->getIncrementalOutput());
    }

    public function testTheServerSendsHeartbeatsWhileARequestIsStillArriving(): void
    {
        $client = $this->startServer();
        $client->sendRaw(['action' => 'page.url', 'pageId' => 'none'], 0.0);
        $frame = LspFraming::encode(json_encode(['requestId' => 'split', 'action' => 'page.url', 'pageId' => 'none'], \JSON_THROW_ON_ERROR));

        $server = $this->server ?? throw new \LogicException('The server is not running.');
        $this->launcher->getInputStream()?->write(substr($frame, 0, intdiv(\strlen($frame), 2)));

        // fails with ProcessTimedOutException instead of waiting forever
        $server->setTimeout(10);
        $heartbeats = 0;
        $server->waitUntil(static function (string $type, string $output) use (&$heartbeats): bool {
            $heartbeats += substr_count($output, '{"type":"heartbeat"}');

            return $heartbeats >= 2;
        });

        $this->assertGreaterThanOrEqual(2, $heartbeats);
    }

    private function startServer(): ProcessJsonRpcClient
    {
        $node = (new ExecutableFinder())->find('node');
        if (null === $node) {
            $this->markTestSkipped('Node.js executable not found.');
        }

        $script = \dirname(__DIR__, 3).'/bin/playwright-server.js';
        $this->launcher = new ProcessLauncher();
        $this->server = $this->launcher->start([$node, $script, '--heartbeat-interval='.self::HEARTBEAT_INTERVAL_MS], \dirname($script));

        return new ProcessJsonRpcClient($this->server, $this->launcher, idleTimeoutMs: self::IDLE_TIMEOUT_MS);
    }
}
