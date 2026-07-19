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
use Playwright\Tests\Functional\FunctionalTestCase;
use Playwright\Tracing\Tracing;

#[CoversClass(Tracing::class)]
#[CoversClass(BrowserContext::class)]
final class TracingApiTest extends FunctionalTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/playwright-tracing-api-'.uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        if (is_dir($this->tempDir)) {
            @rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    public function testTracingStartAndStopProduceATraceArchive(): void
    {
        $tracePath = $this->tempDir.'/trace.zip';

        $this->goto('/index.html');

        $tracing = $this->context->tracing();
        $tracing->start(['screenshots' => false, 'snapshots' => true]);

        $this->page->locator('#heading')->textContent();

        $tracing->stop(['path' => $tracePath]);

        self::assertFileExists($tracePath);
        self::assertGreaterThan(0, (int) filesize($tracePath));
        self::assertStringContainsString('textContent', $this->readTraceEvents($tracePath));
    }

    public function testGroupsAppearInTheTrace(): void
    {
        $tracePath = $this->tempDir.'/trace-group.zip';

        $this->goto('/index.html');

        $tracing = $this->context->tracing();
        $tracing->start(['snapshots' => true]);

        $tracing->group('my custom step');
        $this->page->locator('#heading')->isVisible();
        $tracing->groupEnd();

        $tracing->stop(['path' => $tracePath]);

        $events = $this->readTraceEvents($tracePath);
        self::assertStringContainsString('"method":"tracingGroup"', $events);
        self::assertStringContainsString('my custom step', $events);
    }

    public function testChunksProduceSeparateArchives(): void
    {
        $chunkPath = $this->tempDir.'/chunk.zip';

        $this->goto('/index.html');

        $tracing = $this->context->tracing();
        $tracing->start(['snapshots' => true]);

        $tracing->startChunk(['title' => 'chunk one']);
        $this->page->locator('#heading')->textContent();
        $tracing->stopChunk(['path' => $chunkPath]);

        $tracing->stop();

        self::assertFileExists($chunkPath);
        self::assertGreaterThan(0, (int) filesize($chunkPath));
    }

    private function readTraceEvents(string $zipPath): string
    {
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($zipPath));

        $events = '';
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = (string) $zip->getNameIndex($i);
            if (str_ends_with($name, '.trace')) {
                $events .= (string) $zip->getFromIndex($i);
            }
        }
        $zip->close();

        self::assertNotSame('', $events, 'No .trace events file found in the archive');

        return $events;
    }
}
