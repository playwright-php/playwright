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

namespace Playwright\Tests\Unit\Video;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Transport\TransportInterface;
use Playwright\Video\Video;

#[CoversClass(Video::class)]
final class VideoTest extends TestCase
{
    private string $tmpDir = '';

    protected function tearDown(): void
    {
        if ('' === $this->tmpDir) {
            return;
        }

        foreach (glob($this->tmpDir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tmpDir.'/nested');
        @rmdir($this->tmpDir);
    }

    public function testPathIsTheRecordingLocation(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->never())->method('send');

        $video = new Video($transport, 'video_1', '/tmp/videos/page.webm');

        $this->assertSame('/tmp/videos/page.webm', $video->path());
    }

    public function testSaveAsAsksTheServerForTheRecording(): void
    {
        $source = $this->createRecording();
        $target = $this->tmpDir.'/copy.webm';

        $transport = $this->createMock(TransportInterface::class);
        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload) use ($target): bool {
                return 'video.saveAs' === $payload['action']
                    && 'video_1' === $payload['videoId']
                    && $target === $payload['path'];
            }))
            ->willReturn([]);

        (new Video($transport, 'video_1', $source))->saveAs($target);

        $this->assertFileExists($target);
        $this->assertSame('recording', file_get_contents($target));
    }

    public function testSaveAsCreatesTheTargetDirectory(): void
    {
        $source = $this->createRecording();
        $target = $this->tmpDir.'/nested/copy.webm';

        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturn([]);

        (new Video($transport, 'video_1', $source))->saveAs($target);

        $this->assertFileExists($target);
    }

    public function testDeleteAsksTheServerAndLeavesNoFileBehind(): void
    {
        $source = $this->createRecording();

        $transport = $this->createMock(TransportInterface::class);
        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'video.delete' === $payload['action'] && 'video_1' === $payload['videoId'];
            }))
            ->willReturn([]);

        (new Video($transport, 'video_1', $source))->delete();

        $this->assertFileDoesNotExist($source);
    }

    private function createRecording(): string
    {
        $this->tmpDir = sys_get_temp_dir().'/pw-php-video-'.bin2hex(random_bytes(6));
        mkdir($this->tmpDir);
        $path = $this->tmpDir.'/page.webm';
        file_put_contents($path, 'recording');

        return $path;
    }
}
