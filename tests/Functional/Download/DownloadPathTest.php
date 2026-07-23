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

namespace Playwright\Tests\Functional\Download;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Browser\BrowserBuilder;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\PlaywrightFactory;
use Playwright\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Process\ExecutableFinder;

#[CoversClass(BrowserBuilder::class)]
final class DownloadPathTest extends FunctionalTestCase
{
    public function testAClickedDownloadLandsInTheConfiguredDirectory(): void
    {
        $node = (new ExecutableFinder())->find('node');
        if (null === $node) {
            self::markTestSkipped('Node.js executable not found.');
        }

        $downloadsDir = sys_get_temp_dir().'/pw-php-downloads-'.uniqid();
        mkdir($downloadsDir, 0777, true);

        $config = new PlaywrightConfig(nodePath: $node, downloadsDir: $downloadsDir);

        $playwright = PlaywrightFactory::create($config);
        $browser = $playwright->chromium()->launch();

        try {
            $page = $browser->context()->newPage();
            $page->setContent(
                '<a id="dl" href="data:text/plain,hello%20world" download="hello.txt">download</a>'
            );
            $page->locator('#dl')->click();

            // The download is asynchronous; Playwright stores the file under
            // downloadsPath. Poll until it lands rather than guessing a delay.
            $file = $this->waitForFirstFile($downloadsDir, 5.0);

            self::assertNotNull($file, 'no file appeared in the downloads directory');
            self::assertGreaterThan(0, (int) filesize($file));
        } finally {
            $browser->close();
            $this->removeDirectory($downloadsDir);
        }
    }

    private function waitForFirstFile(string $directory, float $timeoutSeconds): ?string
    {
        $deadline = microtime(true) + $timeoutSeconds;

        do {
            foreach (glob($directory.'/*') ?: [] as $entry) {
                if (is_file($entry)) {
                    return $entry;
                }
                if (is_dir($entry)) {
                    foreach (glob($entry.'/*') ?: [] as $nested) {
                        if (is_file($nested)) {
                            return $nested;
                        }
                    }
                }
            }
            usleep(100_000);
        } while (microtime(true) < $deadline);

        return null;
    }

    private function removeDirectory(string $directory): void
    {
        foreach (glob($directory.'/*') ?: [] as $entry) {
            if (is_dir($entry)) {
                foreach (glob($entry.'/*') ?: [] as $nested) {
                    @unlink($nested);
                }
                @rmdir($entry);
            } else {
                @unlink($entry);
            }
        }

        if (is_dir($directory)) {
            @rmdir($directory);
        }
    }
}
