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

namespace Playwright\Tests\Integration\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Page\Page;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Page::class)]
final class PdfIntegrationTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    private string $pdfDir;

    protected function setUp(): void
    {
        $this->pdfDir = sys_get_temp_dir().'/playwright-pdf-test-'.uniqid('', true);
        mkdir($this->pdfDir, 0755, true);

        $config = new PlaywrightConfig(
            screenshotDir: $this->pdfDir,
            headless: true
        );

        $this->setUpPlaywright(null, $config);
        $this->installRouteServer($this->page, [
            '/invoice.html' => <<<'HTML'
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Invoice</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 24px; }
                        header { border-bottom: 2px solid #333; margin-bottom: 16px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
                        th, td { padding: 8px; border-bottom: 1px solid #ddd; text-align: left; }
                        tfoot td { font-weight: bold; }
                    </style>
                </head>
                <body>
                    <header>
                        <h1>Playwright PHP</h1>
                        <p>Invoice #PW-001</p>
                    </header>
                    <section>
                        <h2>Summary</h2>
                        <table>
                            <thead>
                                <tr><th>Item</th><th>Qty</th><th>Price</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Browser automation consulting</td><td>1</td><td>$4,000</td></tr>
                                <tr><td>PDF implementation</td><td>1</td><td>$2,500</td></tr>
                            </tbody>
                            <tfoot>
                                <tr><td colspan="2">Total</td><td>$6,500</td></tr>
                            </tfoot>
                        </table>
                    </section>
                </body>
                </html>
            HTML,
        ]);
        $this->page->goto($this->routeUrl('/invoice.html'));
    }

    protected function tearDown(): void
    {
        $this->tearDownPlaywright();

        $this->cleanPdfDirectory();
    }

    #[Test]
    public function itGeneratesPdfToProvidedPath(): void
    {
        $pdfPath = $this->pdfDir.'/invoice.pdf';

        $result = $this->page->pdf($pdfPath, ['format' => 'A4']);

        $this->assertSame($pdfPath, $result);
        $this->assertFileExists($pdfPath);
        $this->assertPdfSignature($pdfPath);
    }

    #[Test]
    public function itReturnsPdfContentWithoutLeavingArtifacts(): void
    {
        $content = $this->page->pdfContent(['printBackground' => true]);

        $this->assertNotEmpty($content);
        $this->assertStringStartsWith('%PDF', $content);

        $this->assertDirectoryHasNoArtifacts();
    }

    private function assertPdfSignature(string $path): void
    {
        $data = file_get_contents($path);
        $this->assertNotFalse($data);
        $this->assertStringStartsWith('%PDF', $data);
        $this->assertGreaterThan(200, strlen($data));
    }

    private function assertDirectoryHasNoArtifacts(): void
    {
        $files = array_diff(scandir($this->pdfDir) ?: [], ['.', '..']);
        $this->assertEmpty($files, 'Temporary PDF artifacts should be cleaned up');
    }

    private function cleanPdfDirectory(): void
    {
        if (!is_dir($this->pdfDir)) {
            return;
        }

        foreach (array_diff(scandir($this->pdfDir) ?: [], ['.', '..']) as $file) {
            @unlink($this->pdfDir.DIRECTORY_SEPARATOR.$file);
        }

        @rmdir($this->pdfDir);
    }
}
