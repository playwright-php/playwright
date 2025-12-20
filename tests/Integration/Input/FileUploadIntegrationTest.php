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

namespace Playwright\Tests\Integration\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\Locator;
use Playwright\Page\Page;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Page::class)]
#[CoversClass(Locator::class)]
final class FileUploadIntegrationTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    private string $tempFile = '';

    public function setUp(): void
    {
        $this->setUpPlaywright();

        // Create a small temporary file to upload
        $this->tempFile = tempnam(sys_get_temp_dir(), 'pwphp_upload_') ?: sys_get_temp_dir().'/pwphp_upload.txt';
        file_put_contents($this->tempFile, 'upload-content');

        $this->installRouteServer($this->page, [
            '/index.html' => <<<'HTML'
                <h1>Upload Test</h1>
                <input type="file" id="file" />
                <script>
                    const input = document.getElementById('file');
                    input.addEventListener('change', (e) => {
                        const file = e.target.files && e.target.files[0];
                        document.body.dataset.filename = file ? file.name : '';
                    });
                </script>
            HTML,
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        if ($this->tempFile && file_exists($this->tempFile)) {
            @unlink($this->tempFile);
        }
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itUploadsAFileWithPageHelper(): void
    {
        $this->page->setInputFiles('#file', [$this->tempFile]);
        usleep(100000);

        $name = $this->page->evaluate('() => document.body.dataset.filename');
        $this->assertSame(basename($this->tempFile), $name);
    }

    #[Test]
    public function itUploadsAFileWithLocator(): void
    {
        $this->page->locator('#file')->setInputFiles([$this->tempFile]);
        usleep(100000);

        $name = $this->page->evaluate('() => document.body.dataset.filename');
        $this->assertSame(basename($this->tempFile), $name);
    }

    #[Test]
    public function itUploadsAFileWithLocatorAndOptionsObject(): void
    {
        $this->page->locator('#file')->setInputFiles(
            [$this->tempFile],
            new \Playwright\Locator\Options\SetInputFilesOptions(noWaitAfter: true)
        );
        usleep(100000);

        $name = $this->page->evaluate('() => document.body.dataset.filename');
        $this->assertSame(basename($this->tempFile), $name);
    }
}
