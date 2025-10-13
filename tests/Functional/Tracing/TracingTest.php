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
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;
use Playwright\Tracing\Tracing;

#[CoversClass(Page::class)]
#[CoversClass(BrowserContext::class)]
#[CoversClass(Tracing::class)]
final class TracingTest extends FunctionalTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/playwright-tracing-'.uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir.'/*');
            if (false !== $files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    public function testCanStartAndStopTracing(): void
    {
        $tracePath = $this->tempDir.'/trace.zip';

        $this->context->startTracing($this->page, [
            'screenshots' => true,
            'snapshots' => true,
        ]);

        $this->goto('/tracing.html');
        $this->page->click('#action-button');

        $this->context->stopTracing($this->page, $tracePath);

        self::assertFileExists($tracePath);
        self::assertGreaterThan(0, filesize($tracePath));
    }

    public function testTracingCapturesInteractions(): void
    {
        $tracePath = $this->tempDir.'/interactions.zip';

        $this->context->startTracing($this->page, [
            'screenshots' => true,
            'snapshots' => true,
        ]);

        $this->goto('/tracing.html');

        $this->page->click('#action-button');
        $this->page->locator('#action-input')->fill('test input');
        $this->page->click('#fetch-button');

        $this->page->waitForSelector('#fetch-result');

        $this->context->stopTracing($this->page, $tracePath);

        self::assertFileExists($tracePath);
        self::assertGreaterThan(1000, filesize($tracePath));
    }

    public function testTracingWithoutScreenshots(): void
    {
        $tracePath = $this->tempDir.'/no-screenshots.zip';

        $this->context->startTracing($this->page, [
            'screenshots' => false,
            'snapshots' => true,
        ]);

        $this->goto('/tracing.html');
        $this->page->click('#action-button');

        $this->context->stopTracing($this->page, $tracePath);

        self::assertFileExists($tracePath);
    }

    public function testTracingWithNavigation(): void
    {
        $tracePath = $this->tempDir.'/navigation.zip';

        $this->context->startTracing($this->page, [
            'screenshots' => true,
            'snapshots' => true,
        ]);

        $this->goto('/tracing.html');
        $this->goto('/index.html');
        $this->goto('/tracing.html');

        $this->context->stopTracing($this->page, $tracePath);

        self::assertFileExists($tracePath);
        self::assertGreaterThan(1000, filesize($tracePath));
    }
}
