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
use Playwright\Testing\Expect;
use Playwright\Tests\Functional\FunctionalTestCase;
use Playwright\Tracing\Tracing;

#[CoversClass(Expect::class)]
#[CoversClass(Tracing::class)]
final class ExpectTraceGroupsTest extends FunctionalTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/playwright-expect-groups-'.uniqid();
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

    public function testExpectAssertionsAppearAsGroupsInTheTrace(): void
    {
        $tracePath = $this->tempDir.'/trace.zip';

        $this->goto('/index.html');

        $this->context->tracing()->start(['snapshots' => true]);

        $this->expect($this->page->locator('#heading'))->toBeVisible();
        $this->expect($this->page)->toHaveTitle('Index - Playwright PHP Tests');

        $this->context->tracing()->stop(['path' => $tracePath]);

        $events = $this->readTraceEvents($tracePath);
        $this->assertStringContainsString('"method":"tracingGroup"', $events);
        $this->assertStringContainsString('expect(#heading).toBeVisible', $events);
        $this->assertStringContainsString('expect(page).toHaveTitle', $events);
    }

    public function testExpectStillWorksWhenTracingIsNotActive(): void
    {
        $this->goto('/index.html');

        $this->expect($this->page->locator('#heading'))->toBeVisible();

        $this->assertTrue(true);
    }

    private function readTraceEvents(string $zipPath): string
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath));

        $events = '';
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = (string) $zip->getNameIndex($i);
            if (str_ends_with($name, '.trace')) {
                $events .= (string) $zip->getFromIndex($i);
            }
        }
        $zip->close();

        $this->assertNotSame('', $events, 'No .trace events file found in the archive');

        return $events;
    }
}
