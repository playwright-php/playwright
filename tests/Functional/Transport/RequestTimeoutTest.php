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

namespace Playwright\Tests\Functional\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\TimeoutException;
use Playwright\PlaywrightFactory;
use Playwright\Tests\Functional\FunctionalTestCase;
use Playwright\Transport\JsonRpc\JsonRpcTransport;
use Symfony\Component\Process\ExecutableFinder;

#[CoversClass(JsonRpcTransport::class)]
final class RequestTimeoutTest extends FunctionalTestCase
{
    public function testAnOperationTimeoutIsNotCutByTheTransportDeadline(): void
    {
        $node = (new ExecutableFinder())->find('node');
        if (null === $node) {
            $this->markTestSkipped('Node.js executable not found.');
        }

        $config = new PlaywrightConfig(nodePath: $node, timeoutMs: 5000);
        $playwright = PlaywrightFactory::create($config);
        $browser = $playwright->chromium()->launch();

        try {
            $context = $browser->newContext();
            $page = $context->newPage();
            $page->setContent('<html><body><p>empty</p></body></html>');

            $start = microtime(true);

            try {
                $page->waitForSelector('#does-not-exist', ['timeout' => 9000]);
                $this->fail('Expected a TimeoutException');
            } catch (TimeoutException $e) {
                $elapsed = microtime(true) - $start;

                // The wait must live for its full 9s and fail with the
                // Playwright error, not be cut at 5s by the RPC deadline.
                $this->assertStringContainsString('Timeout 9000ms exceeded', $e->getMessage());
                $this->assertGreaterThan(8.0, $elapsed);
            }
        } finally {
            $browser->close();
        }
    }
}
