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

namespace Playwright\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\RuntimeException;
use Playwright\Page\Page;
use Playwright\Transport\TransportInterface;

#[CoversClass(Page::class)]
final class PagePdfTest extends TestCase
{
    public function testPdfUsesProvidedPath(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $expectedPath = sys_get_temp_dir().'/playwright-pdf-unit-test.pdf';

        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) use ($expectedPath) {
                $this->assertSame('page.pdf', $payload['action']);
                $this->assertSame('page-unit', $payload['pageId']);
                $this->assertSame($expectedPath, $payload['options']['path'] ?? null);

                return true;
            }))
            ->willReturn([]);

        $page = new Page($transport, $context, 'page-unit');

        $result = $page->pdf($expectedPath);

        $this->assertSame($expectedPath, $result);
    }

    public function testPdfContentReturnsBinaryAndCleansUpTempFile(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $pdfDir = sys_get_temp_dir().'/playwright-pdf-content-'.uniqid('', true);
        mkdir($pdfDir, 0755, true);

        $config = new PlaywrightConfig(screenshotDir: $pdfDir);
        $pdfBytes = '%PDF-1.4 mock';

        $transport->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (array $payload) use ($pdfBytes): array {
                $this->assertSame('page.pdf', $payload['action']);
                $path = $payload['options']['path'] ?? null;
                $this->assertIsString($path);
                file_put_contents($path, $pdfBytes);

                return [];
            });

        $page = new Page($transport, $context, 'page-unit', $config);

        $content = $page->pdfContent();

        $this->assertSame($pdfBytes, $content);
        $this->assertDirectoryHasNoFiles($pdfDir);

        rmdir($pdfDir);
    }

    public function testPdfContentRejectsPathOption(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $page = new Page($transport, $context, 'page-unit');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Do not provide a "path" option when requesting inline PDF content.');

        $page->pdfContent(['path' => '/tmp/should-not-be-used.pdf']);
    }

    private function assertDirectoryHasNoFiles(string $directory): void
    {
        $files = array_diff(scandir($directory) ?: [], ['.', '..']);
        $this->assertEmpty($files, sprintf('Directory %s should be empty', $directory));
    }
}
