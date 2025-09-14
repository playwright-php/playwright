<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Screenshot;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Screenshot\ScreenshotHelper;

#[CoversClass(ScreenshotHelper::class)]
class ScreenshotHelperTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir().'/playwright-test-'.uniqid('', true);
        mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            array_map('unlink', glob($this->testDir.'/*.png'));
            rmdir($this->testDir);
        }
    }

    public function testSlugifyUrl(): void
    {
        $testCases = [
            ['https://example.com', 'example-com'],
            ['https://www.github.com/user/repo', 'github-com-user-repo'],
            ['http://api.service.com/v1/users?id=123', 'api-service-com-v1-users-id-123'],
            ['https://sub.domain.co.uk/path/file.html', 'sub-domain-co-uk-path-file-html'],
            ['invalid-chars!@#$%^&*()', 'invalid-chars'],
            ['', 'screenshot'],
        ];

        foreach ($testCases as [$input, $expected]) {
            $result = ScreenshotHelper::slugifyUrl($input);
            $this->assertEquals($expected, $result, "Failed for input: $input");
        }
    }

    public function testSlugifyUrlWithMaxLength(): void
    {
        $longUrl = 'https://example.com/very-long-path-that-exceeds-the-maximum-length-limit';
        $result = ScreenshotHelper::slugifyUrl($longUrl, 20);

        $this->assertLessThanOrEqual(20, strlen($result));
        $this->assertEquals('example-com-very-lon', $result);
    }

    public function testGenerateFilename(): void
    {
        $url = 'https://github.com/user/repo';
        $filename = ScreenshotHelper::generateFilename($url, $this->testDir);

        $basename = basename($filename);
        $this->assertMatchesRegularExpression(
            '/^\d{8}_\d{6}_\d{3}_github-com-user-repo\.png$/',
            $basename
        );

        $this->assertDirectoryExists($this->testDir);
        $this->assertEquals($this->testDir.'/'.$basename, $filename);
    }

    public function testGenerateFilenameCreatesDirectory(): void
    {
        $nonExistentDir = $this->testDir.'/nested/subdir';
        $this->assertDirectoryDoesNotExist($nonExistentDir);

        $filename = ScreenshotHelper::generateFilename('https://example.com', $nonExistentDir);

        $this->assertDirectoryExists($nonExistentDir);
        $this->assertStringStartsWith($nonExistentDir.'/', $filename);
    }

    public function testEnsureDirectoryExists(): void
    {
        $newDir = $this->testDir.'/new/nested/directory';
        $this->assertDirectoryDoesNotExist($newDir);

        ScreenshotHelper::ensureDirectoryExists($newDir);

        $this->assertDirectoryExists($newDir);
    }

    public function testEnsureDirectoryExistsIdempotent(): void
    {
        ScreenshotHelper::ensureDirectoryExists($this->testDir);
        ScreenshotHelper::ensureDirectoryExists($this->testDir);

        $this->assertDirectoryExists($this->testDir);
    }

    public function testGetDirectoryInfoEmpty(): void
    {
        $info = ScreenshotHelper::getDirectoryInfo($this->testDir);

        $expected = [
            'count' => 0,
            'totalSize' => 0,
            'oldestFile' => null,
            'newestFile' => null,
        ];

        $this->assertEquals($expected, $info);
    }

    public function testGetDirectoryInfoWithFiles(): void
    {
        file_put_contents($this->testDir.'/old.png', 'fake png data 1');
        sleep(1);
        file_put_contents($this->testDir.'/new.png', 'fake png data 2 longer');
        file_put_contents($this->testDir.'/not-png.txt', 'should be ignored');

        $info = ScreenshotHelper::getDirectoryInfo($this->testDir);

        $this->assertEquals(2, $info['count']);
        $this->assertEquals(37, $info['totalSize']);
        $this->assertEquals('old.png', $info['oldestFile']);
        $this->assertEquals('new.png', $info['newestFile']);
    }

    public function testCleanupOldScreenshots(): void
    {
        $oldFile = $this->testDir.'/old.png';
        $newFile = $this->testDir.'/new.png';

        file_put_contents($oldFile, 'old');
        file_put_contents($newFile, 'new');

        touch($oldFile, time() - 7200);

        $cleaned = ScreenshotHelper::cleanupOldScreenshots($this->testDir, 3600);

        $this->assertEquals(1, $cleaned);
        $this->assertFileDoesNotExist($oldFile);
        $this->assertFileExists($newFile);
    }

    public function testCleanupOldScreenshotsByCount(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $file = $this->testDir."/file{$i}.png";
            file_put_contents($file, "data $i");
            touch($file, time() - $i);
            usleep(1000);
        }

        $cleaned = ScreenshotHelper::cleanupOldScreenshots($this->testDir, 86400, 3);

        $this->assertEquals(2, $cleaned);

        $remaining = glob($this->testDir.'/*.png');
        $this->assertCount(3, $remaining);

        $this->assertFileExists($this->testDir.'/file1.png');
        $this->assertFileExists($this->testDir.'/file2.png');
        $this->assertFileExists($this->testDir.'/file3.png');
        $this->assertFileDoesNotExist($this->testDir.'/file4.png');
        $this->assertFileDoesNotExist($this->testDir.'/file5.png');
    }

    public function testCleanupNonExistentDirectory(): void
    {
        $cleaned = ScreenshotHelper::cleanupOldScreenshots('/non/existent/directory');
        $this->assertEquals(0, $cleaned);
    }

    public function testGetDirectoryInfoNonExistent(): void
    {
        $info = ScreenshotHelper::getDirectoryInfo('/non/existent/directory');

        $expected = [
            'count' => 0,
            'totalSize' => 0,
            'oldestFile' => null,
            'newestFile' => null,
        ];

        $this->assertEquals($expected, $info);
    }

    public function testFilenameUniqueness(): void
    {
        $url = 'https://example.com';

        $filenames = [];
        for ($i = 0; $i < 5; ++$i) {
            $filenames[] = basename(ScreenshotHelper::generateFilename($url, $this->testDir));
            usleep(1000);
        }

        $uniqueFilenames = array_unique($filenames);
        $this->assertCount(5, $uniqueFilenames, 'Generated filenames should be unique');
    }
}
