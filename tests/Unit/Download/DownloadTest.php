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

namespace Playwright\Tests\Unit\Download;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Download\Download;
use Playwright\Page\PageInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(Download::class)]
final class DownloadTest extends TestCase
{
    public function testCancel(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'download.cancel' === $payload['action'] && 'dl-123' === $payload['downloadId'];
            }));

        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);
        $download->cancel();
    }

    public function testDelete(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'download.delete' === $payload['action'] && 'dl-123' === $payload['downloadId'];
            }));

        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);
        $download->delete();
    }

    public function testFailure(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->willReturn(['error' => 'Download failed']);

        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);

        $this->assertSame('Download failed', $download->failure());
    }

    public function testFailureReturnsNull(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);

        $this->assertNull($download->failure());
    }

    public function testPage(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);

        $this->assertSame($page, $download->page());
    }

    public function testPath(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->willReturn(['path' => '/tmp/download.pdf']);

        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);

        $this->assertSame('/tmp/download.pdf', $download->path());
    }

    public function testPathReturnsNull(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);

        $this->assertNull($download->path());
    }

    public function testSaveAs(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'download.saveAs' === $payload['action']
                    && 'dl-123' === $payload['downloadId']
                    && '/path/to/file.pdf' === $payload['path'];
            }));

        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);
        $download->saveAs('/path/to/file.pdf');
    }

    public function testSuggestedFilename(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', ['suggestedFilename' => 'document.pdf']);

        $this->assertSame('document.pdf', $download->suggestedFilename());
    }

    public function testSuggestedFilenameReturnsEmpty(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);

        $this->assertSame('', $download->suggestedFilename());
    }

    public function testUrl(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', ['url' => 'https://example.com/file.pdf']);

        $this->assertSame('https://example.com/file.pdf', $download->url());
    }

    public function testUrlReturnsEmpty(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);

        $this->assertSame('', $download->url());
    }

    public function testCreateReadStream(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $page = $this->createMock(PageInterface::class);
        $download = new Download($transport, $page, 'dl-123', []);

        $this->assertNull($download->createReadStream());
    }
}
