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

namespace Playwright\Tests\Unit\Locator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\PlaywrightException;
use Playwright\Locator\Locator;
use Playwright\Transport\TransportInterface;

#[CoversClass(Locator::class)]
final class LocatorSetInputFilesTest extends TestCase
{
    private string $originalCwd;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->originalCwd = (string) getcwd();
        $this->tmpDir = sys_get_temp_dir().'/pw-input-files-'.bin2hex(random_bytes(6));
        mkdir($this->tmpDir);
        $this->tmpDir = (string) realpath($this->tmpDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);

        foreach (glob($this->tmpDir.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->tmpDir);
    }

    public function testRelativePathIsResolvedBeforeItReachesTheTransport(): void
    {
        file_put_contents($this->tmpDir.'/upload.txt', 'hello');
        chdir($this->tmpDir);

        $sent = null;
        $transport = $this->createMock(TransportInterface::class);
        $transport->method('send')->willReturnCallback(function (array $payload) use (&$sent): array {
            $sent = $payload;

            return [];
        });

        (new Locator($transport, 'page1', '#f'))->setInputFiles('upload.txt');

        self::assertSame([$this->tmpDir.'/upload.txt'], $sent['files']);
    }

    public function testMissingFileIsRejectedBeforeAnythingIsSent(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects(self::never())->method('send');

        $this->expectException(PlaywrightException::class);
        $this->expectExceptionMessage('File not found: nope.txt');

        (new Locator($transport, 'page1', '#f'))->setInputFiles('nope.txt');
    }
}
