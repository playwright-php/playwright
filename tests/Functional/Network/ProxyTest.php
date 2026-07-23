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

namespace Playwright\Tests\Functional\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Browser\BrowserBuilder;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\PlaywrightFactory;
use Playwright\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Process\ExecutableFinder;

#[CoversClass(BrowserBuilder::class)]
final class ProxyTest extends FunctionalTestCase
{
    public function testNavigationIsRoutedThroughTheConfiguredProxy(): void
    {
        $node = (new ExecutableFinder())->find('node');
        if (null === $node) {
            self::markTestSkipped('Node.js executable not found.');
        }

        // A dead proxy (nothing listens on port 1) plus a non-loopback target,
        // so Chromium does not bypass it. If the proxy reaches the browser, the
        // failure is a proxy-connection error; if it were ignored, the target
        // would instead fail to resolve. The error kind is the assertion.
        $config = new PlaywrightConfig(
            nodePath: $node,
            proxy: ['server' => 'http://127.0.0.1:1'],
        );

        $playwright = PlaywrightFactory::create($config);
        $browser = $playwright->chromium()->launch();

        try {
            $page = $browser->context()->newPage();

            $message = null;
            try {
                $page->goto('http://example.invalid/');
            } catch (\Throwable $e) {
                $message = $e->getMessage();
            }

            self::assertNotNull($message, 'navigation through a dead proxy should fail');
            self::assertStringContainsStringIgnoringCase('PROXY', $message);
        } finally {
            $browser->close();
        }
    }
}
