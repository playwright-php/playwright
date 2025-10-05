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

namespace Playwright\Tests\Unit\FileChooser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\FileChooser\FileChooser;
use Playwright\Page\PageInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(FileChooser::class)]
final class FileChooserTest extends TestCase
{
    public function testElement(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $page = $this->createMock(PageInterface::class);
        $fileChooser = new FileChooser($transport, $page, 'element-1', []);

        $this->assertNull($fileChooser->element());
    }

    public function testIsMultiple(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $page = $this->createMock(PageInterface::class);
        $fileChooser = new FileChooser($transport, $page, 'element-1', ['isMultiple' => true]);

        $this->assertTrue($fileChooser->isMultiple());
    }

    public function testIsMultipleReturnsFalse(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $page = $this->createMock(PageInterface::class);
        $fileChooser = new FileChooser($transport, $page, 'element-1', []);

        $this->assertFalse($fileChooser->isMultiple());
    }

    public function testPage(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $page = $this->createMock(PageInterface::class);
        $fileChooser = new FileChooser($transport, $page, 'element-1', []);

        $this->assertSame($page, $fileChooser->page());
    }

    public function testSetFilesWithString(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'fileChooser.setFiles' === $payload['action']
                    && 'element-1' === $payload['elementId']
                    && ['/path/to/file.txt'] === $payload['files'];
            }));

        $page = $this->createMock(PageInterface::class);
        $fileChooser = new FileChooser($transport, $page, 'element-1', []);
        $fileChooser->setFiles('/path/to/file.txt');
    }

    public function testSetFilesWithArray(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'fileChooser.setFiles' === $payload['action']
                    && 'element-1' === $payload['elementId']
                    && ['/path/file1.txt', '/path/file2.txt'] === $payload['files'];
            }));

        $page = $this->createMock(PageInterface::class);
        $fileChooser = new FileChooser($transport, $page, 'element-1', []);
        $fileChooser->setFiles(['/path/file1.txt', '/path/file2.txt']);
    }

    public function testSetFilesWithOptions(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'fileChooser.setFiles' === $payload['action']
                    && isset($payload['options'])
                    && 1000 === $payload['options']['timeout'];
            }));

        $page = $this->createMock(PageInterface::class);
        $fileChooser = new FileChooser($transport, $page, 'element-1', []);
        $fileChooser->setFiles('/path/to/file.txt', ['timeout' => 1000]);
    }
}