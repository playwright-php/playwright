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

namespace Playwright\Tests\Functional\Video;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Browser\Browser;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\PlaywrightFactory;
use Playwright\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Process\ExecutableFinder;

#[CoversClass(Browser::class)]
final class VideoRecordingTest extends FunctionalTestCase
{
    public function testAnExplicitContextRecordsVideoIntoTheConfiguredDirectory(): void
    {
        $node = $this->requireNode();
        $videosDir = sys_get_temp_dir().'/pw-php-videos-'.uniqid();

        $config = new PlaywrightConfig(nodePath: $node, videosDir: $videosDir);

        $playwright = PlaywrightFactory::create($config);
        $browser = $playwright->chromium()->launch();

        try {
            $context = $browser->newContext();
            $page = $context->newPage();
            $page->goto($this->getBaseUrl().'/index.html');

            // Playwright flushes the video file when the context closes.
            $context->close();

            $videos = glob($videosDir.'/*.webm') ?: [];
            self::assertCount(1, $videos);
            self::assertGreaterThan(0, (int) filesize($videos[0]));
        } finally {
            $browser->close();
            $this->removeDirectory($videosDir);
        }
    }

    public function testTheDefaultContextRecordsVideoToo(): void
    {
        $node = $this->requireNode();
        $videosDir = sys_get_temp_dir().'/pw-php-videos-default-'.uniqid();

        $config = new PlaywrightConfig(nodePath: $node, videosDir: $videosDir);

        $playwright = PlaywrightFactory::create($config);
        $browser = $playwright->chromium()->launch();

        try {
            // No newContext() call: this is the context the server built at launch.
            $page = $browser->context()->newPage();
            $page->goto($this->getBaseUrl().'/index.html');

            $browser->context()->close();

            $videos = glob($videosDir.'/*.webm') ?: [];
            self::assertCount(1, $videos);
            self::assertGreaterThan(0, (int) filesize($videos[0]));
        } finally {
            $browser->close();
            $this->removeDirectory($videosDir);
        }
    }

    private function requireNode(): string
    {
        $node = (new ExecutableFinder())->find('node');
        if (null === $node) {
            self::markTestSkipped('Node.js executable not found.');
        }

        return $node;
    }

    private function removeDirectory(string $directory): void
    {
        foreach (glob($directory.'/*') ?: [] as $file) {
            @unlink($file);
        }

        if (is_dir($directory)) {
            @rmdir($directory);
        }
    }
}
