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

namespace Playwright\Tests\Functional\Tracing;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Browser\BrowserContext;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\PlaywrightFactory;
use Playwright\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Process\ExecutableFinder;

#[CoversClass(BrowserContext::class)]
final class AutoTracingTest extends FunctionalTestCase
{
    public function testTraceIsSavedOnCloseWhenConfigEnablesTracing(): void
    {
        $node = (new ExecutableFinder())->find('node');
        if (null === $node) {
            self::markTestSkipped('Node.js executable not found.');
        }

        $traceDir = sys_get_temp_dir().'/pw-php-auto-trace-'.uniqid();

        $config = new PlaywrightConfig(
            nodePath: $node,
            tracingEnabled: true,
            traceDir: $traceDir,
            traceScreenshots: true,
            traceSnapshots: true,
        );

        $playwright = PlaywrightFactory::create($config);
        $browser = $playwright->chromium()->launch();

        try {
            $context = $browser->newContext();
            $page = $context->newPage();
            $page->goto($this->getBaseUrl().'/index.html');

            $context->close();

            $traces = glob($traceDir.'/trace-*.zip') ?: [];
            self::assertCount(1, $traces);
            self::assertGreaterThan(0, (int) filesize($traces[0]));
        } finally {
            $browser->close();

            foreach (glob($traceDir.'/*.zip') ?: [] as $file) {
                @unlink($file);
            }
            if (is_dir($traceDir)) {
                @rmdir($traceDir);
            }
        }
    }
}
