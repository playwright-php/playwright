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
use PHPUnit\Framework\TestCase;
use Playwright\Transport\JsonRpc\LspFraming;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

#[CoversNothing]
#[Group('integration')]
final class StdinChunkTest extends TestCase
{
    private const SLOW_TIMEOUT_MS = 5000;

    private ?Process $bridge = null;
    private ?InputStream $input = null;
    private string $buffer = '';

    protected function tearDown(): void
    {
        $this->input?->close();
        $this->bridge?->stop(5);
        $this->bridge = null;
        $this->input = null;

        parent::tearDown();
    }

    public function testACommandIsNotHeldBackByASlowerOneFromTheSameChunk(): void
    {
        $pageId = $this->openPage();

        $this->input?->write(
            $this->frame(['requestId' => 'slow', 'action' => 'locator.waitFor', 'pageId' => $pageId, 'selector' => '#never', 'options' => ['timeout' => self::SLOW_TIMEOUT_MS]])
            .$this->frame(['requestId' => 'fast', 'action' => 'page.url', 'pageId' => $pageId])
        );

        $start = microtime(true);
        $first = $this->nextResponse(['slow', 'fast']);

        $this->assertSame('fast', $first['requestId']);
        $this->assertLessThan(self::SLOW_TIMEOUT_MS / 1000, microtime(true) - $start);
    }

    private function openPage(): string
    {
        $node = (new ExecutableFinder())->find('node');
        if (null === $node) {
            $this->markTestSkipped('Node.js executable not found.');
        }

        $this->input = new InputStream();
        $this->bridge = new Process([$node, \dirname(__DIR__, 3).'/bin/playwright-server.js']);
        $this->bridge->setInput($this->input);
        $this->bridge->setTimeout(null);
        $this->bridge->start();

        $this->input->write($this->frame(['requestId' => 'launch', 'action' => 'launch', 'browser' => 'chromium', 'options' => ['headless' => true]]));
        $launch = $this->nextResponse(['launch']);
        $this->assertIsString($launch['defaultContextId'] ?? null, json_encode($launch) ?: '');

        $this->input->write($this->frame(['requestId' => 'page', 'action' => 'context.newPage', 'contextId' => $launch['defaultContextId'], 'options' => []]));
        $page = $this->nextResponse(['page']);
        $this->assertIsString($page['pageId'] ?? null, json_encode($page) ?: '');

        return $page['pageId'];
    }

    /**
     * @param array<string, mixed> $command
     */
    private function frame(array $command): string
    {
        return LspFraming::encode(json_encode($command, \JSON_THROW_ON_ERROR));
    }

    /**
     * @param list<string> $requestIds
     *
     * @return array<string, mixed>
     */
    private function nextResponse(array $requestIds): array
    {
        $bridge = $this->bridge ?? throw new \LogicException('The bridge is not running.');
        $deadline = microtime(true) + 60;

        while (microtime(true) < $deadline) {
            if (!$bridge->isRunning()) {
                $this->fail('The bridge exited: '.$bridge->getErrorOutput());
            }

            $this->buffer .= $bridge->getIncrementalOutput();
            $decoded = LspFraming::decode($this->buffer);
            $this->buffer = $decoded['remainingBuffer'];

            foreach ($decoded['messages'] as $message) {
                $data = json_decode($message, true);
                if (\is_array($data) && \in_array($data['requestId'] ?? null, $requestIds, true)) {
                    return $data;
                }
            }

            usleep(10_000);
        }

        $this->fail(\sprintf('No response for %s within 60s.', implode(', ', $requestIds)));
    }
}
