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

namespace Playwright\Tests\Functional\Screenshot;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Locator\Locator;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Locator::class)]
final class ScreenshotTest extends FunctionalTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = \sys_get_temp_dir().'/_pw_test_screenshots_'.\uniqid();
        if (!\is_dir($this->tempDir)) {
            \mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (\is_dir($this->tempDir)) {
            $files = \glob($this->tempDir.'/*');
            if (false !== $files) {
                foreach ($files as $file) {
                    if (\is_file($file)) {
                        \unlink($file);
                    }
                }
            }
            \rmdir($this->tempDir);
        }

        parent::tearDown();
    }

    public function testCanTakePageScreenshot(): void
    {
        $this->goto('/screenshot.html');

        $path = $this->tempDir.'/page.png';
        $this->page->screenshot($path);

        self::assertFileExists($path);

        $content = \file_get_contents($path);
        self::assertNotFalse($content);
        self::assertStringStartsWith(\base64_decode('iVBORw0KGgo='), $content);
    }

    public function testCanTakeElementScreenshot(): void
    {
        $this->goto('/screenshot.html');

        $element = $this->page->locator('#element-to-capture');

        $path = $this->tempDir.'/element.png';
        $element->screenshot($path);

        self::assertFileExists($path);

        $content = \file_get_contents($path);
        self::assertNotFalse($content);
        self::assertStringStartsWith(\base64_decode('iVBORw0KGgo='), $content);
    }

    public function testCanTakeFullPageScreenshot(): void
    {
        $this->goto('/screenshot.html');

        $path = $this->tempDir.'/fullpage.png';
        $this->page->screenshot($path, ['fullPage' => true]);

        self::assertFileExists($path);

        $content = \file_get_contents($path);
        self::assertNotFalse($content);

        $size = \getimagesize($path);
        self::assertIsArray($size);
        self::assertGreaterThan(1000, $size[1]);
    }

    public function testScreenshotReturnsPath(): void
    {
        $this->goto('/screenshot.html');

        $path = $this->page->screenshot();

        self::assertIsString($path);
        self::assertFileExists($path);

        \unlink($path);
    }

    public function testCanTakeScreenshotWithCustomOptions(): void
    {
        $this->goto('/screenshot.html');

        $path = $this->tempDir.'/custom.png';
        $this->page->screenshot($path, ['type' => 'png']);

        self::assertFileExists($path);

        $info = \getimagesize($path);
        self::assertIsArray($info);
        self::assertSame('image/png', $info['mime']);
    }
}
