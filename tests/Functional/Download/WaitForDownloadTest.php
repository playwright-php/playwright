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
use Playwright\Download\Download;
use Playwright\Exception\TimeoutException;
use Playwright\Page\Options\WaitForDownloadOptions;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Download::class)]
final class WaitForDownloadTest extends FunctionalTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/pw-php-wait-for-download-'.uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->goto('/download.html');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);

        parent::tearDown();
    }

    public function testReturnsTheDownloadStartedByTheAction(): void
    {
        $download = $this->page->waitForDownload(fn () => $this->page->locator('#report')->click());

        $this->assertSame('report.csv', $download->suggestedFilename());
        $this->assertSame($this->getBaseUrl().'/download/report.csv', $download->url());
        $this->assertSame($this->page, $download->page());
        $this->assertNull($download->failure());

        $path = $download->path();
        $this->assertIsString($path);
        $this->assertSame("id,name\n1,Ada\n", file_get_contents($path));
    }

    public function testSavesAndStreamsTheDownloadedFile(): void
    {
        $download = $this->page->waitForDownload(fn () => $this->page->locator('#report')->click());

        $target = $this->tempDir.'/saved.csv';
        $download->saveAs($target);
        $this->assertSame("id,name\n1,Ada\n", file_get_contents($target));

        $stream = $download->createReadStream();
        $this->assertIsResource($stream);
        $this->assertSame("id,name\n1,Ada\n", stream_get_contents($stream));
    }

    public function testDeleteRemovesTheDownloadedFile(): void
    {
        $download = $this->page->waitForDownload(fn () => $this->page->locator('#report')->click());
        $path = $download->path();
        $this->assertIsString($path);
        $this->assertFileExists($path);

        $download->delete();

        $this->assertFileDoesNotExist($path);
    }

    public function testTakesTheFilenameFromTheDownloadAttribute(): void
    {
        $download = $this->page->waitForDownload(fn () => $this->page->locator('#inline')->click());

        $this->assertSame('hello.txt', $download->suggestedFilename());
        $path = $download->path();
        $this->assertIsString($path);
        $this->assertSame('hello world', file_get_contents($path));
    }

    public function testACanceledDownloadReportsItsFailure(): void
    {
        $download = $this->page->waitForDownload(fn () => $this->page->locator('#slow')->click());

        $download->cancel();

        $this->assertSame('canceled', $download->failure());
    }

    public function testThrowsWhenNoDownloadStartsInTime(): void
    {
        $this->expectException(TimeoutException::class);

        $this->page->waitForDownload(
            fn () => $this->page->locator('#nothing')->click(),
            new WaitForDownloadOptions(timeout: 500),
        );
    }

    public function testThePageStaysUsableAfterATimeout(): void
    {
        try {
            $this->page->waitForDownload(fn () => null, ['timeout' => 300]);
            $this->fail('Expected a TimeoutException');
        } catch (TimeoutException) {
        }

        $download = $this->page->waitForDownload(fn () => $this->page->locator('#report')->click());

        $this->assertSame('report.csv', $download->suggestedFilename());
    }
}
