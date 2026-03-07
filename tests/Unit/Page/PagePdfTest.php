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

        $pdfBytes = base64_encode('%PDF-1.4 mock');

        $matcher = $this->exactly(2);

        $transport->expects($matcher)
            ->method('send')
            ->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    self::assertEquals('page.url', $parameters[0]['action']);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    self::assertEquals('page.pdf', $parameters[0]['action']);
                }
            })
            ->willReturnOnConsecutiveCalls(['value' => 'url'], ['binary' => $pdfBytes]);

        $page = new Page($transport, $context, 'page-unit', new PlaywrightConfig());

        $result = $page->pdf($expectedPath);

        $this->assertSame($expectedPath, $result);

        unlink($expectedPath);
    }

    public function testPdfContentReturnsBinaryAndCleansUpTempFile(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $pdfDir = sys_get_temp_dir().'/playwright-pdf-content-'.uniqid('', true);
        mkdir($pdfDir, 0755, true);

        $config = new PlaywrightConfig(screenshotDir: $pdfDir);
        $pdfBytes = '%PDF-1.4 mock';

        $matcher = $this->exactly(1);

        $transport->expects($matcher)
            ->method('send')
            ->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    self::assertEquals('page.pdf', $parameters[0]['action']);
                }
            })
            ->willReturn(['binary' => base64_encode($pdfBytes)]);

        $page = new Page($transport, $context, 'page-unit', $config);

        $content = $page->pdfContent();

        $this->assertSame($pdfBytes, $content);
        $this->assertDirectoryHasNoFiles($pdfDir);

        rmdir($pdfDir);
    }

    private function assertDirectoryHasNoFiles(string $directory): void
    {
        $files = array_diff(scandir($directory) ?: [], ['.', '..']);
        $this->assertEmpty($files, sprintf('Directory %s should be empty', $directory));
    }
}
