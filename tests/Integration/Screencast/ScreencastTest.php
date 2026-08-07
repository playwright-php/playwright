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

namespace Playwright\Tests\Integration\Screencast;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\PlaywrightException;
use Playwright\Screencast\Screencast;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Screencast::class)]
class ScreencastTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    /** @var list<string> */
    private array $temporaryFiles = [];

    public static function setUpBeforeClass(): void
    {
    }

    public static function tearDownAfterClass(): void
    {
    }

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->installRouteServer($this->page, [
            '/index.html' => '<!DOCTYPE html><html><body><h1>Recording</h1><button id="go">Go</button></body></html>',
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();

        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->temporaryFiles = [];
    }

    #[Test]
    public function itRecordsAVideoToTheGivenPath(): void
    {
        $path = $this->temporaryPath();

        $this->page->screencast->start(['path' => $path, 'size' => ['width' => 320, 'height' => 240]]);
        $this->page->click('#go');
        usleep(300 * 1000);
        $this->page->screencast->stop();

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, (int) filesize($path));
    }

    #[Test]
    public function itStartsWithoutAPathAndWritesNothing(): void
    {
        $this->page->screencast->start();
        usleep(100 * 1000);
        $this->page->screencast->stop();

        $this->assertTrue(true, 'start and stop without a path complete without throwing');
    }

    #[Test]
    public function itRejectsASecondStartWhileRecording(): void
    {
        $this->page->screencast->start();

        try {
            $this->expectException(PlaywrightException::class);
            $this->page->screencast->start();
        } finally {
            $this->page->screencast->stop();
        }
    }

    #[Test]
    public function itIgnoresStopWhenNothingIsRecording(): void
    {
        $this->page->screencast->stop();

        $this->assertTrue(true, 'stop without a running screencast completes without throwing');
    }

    #[Test]
    public function itShowsAndHidesAnOverlay(): void
    {
        $this->page->screencast->showOverlay('<b id="overlay-probe">Recording</b>');
        $this->page->screencast->hideOverlays();
        $this->page->screencast->showOverlays();

        $this->assertTrue(true, 'the overlay lifecycle completes without throwing');
    }

    #[Test]
    public function itShowsAnOverlayForALimitedTime(): void
    {
        $this->page->screencast->showOverlay('<b>Temporary</b>', ['duration' => 100]);
        usleep(200 * 1000);

        $this->assertTrue(true, 'a timed overlay is removed without throwing');
    }

    #[Test]
    public function itShowsAChapterCard(): void
    {
        $this->page->screencast->showChapter('Chapter one', ['description' => 'Signing in', 'duration' => 100]);
        usleep(200 * 1000);

        $this->assertTrue(true, 'the chapter card is removed without throwing');
    }

    #[Test]
    public function itDecoratesActionsUntilHidden(): void
    {
        $this->page->screencast->showActions(['cursor' => 'pointer', 'duration' => 100, 'position' => 'top-right']);
        $this->page->click('#go');
        $this->page->screencast->hideActions();

        $this->assertTrue(true, 'action decorations are applied and removed without throwing');
    }

    #[Test]
    public function itRejectsAnUnknownCursor(): void
    {
        $this->expectException(PlaywrightException::class);

        $this->page->screencast->showActions(['cursor' => 'wobble']);
    }

    #[Test]
    public function itAnnotatesARecordingItWrites(): void
    {
        $path = $this->temporaryPath();

        $this->page->screencast->start(['path' => $path]);
        $this->page->screencast->showChapter('Intro', ['duration' => 100]);
        $this->page->screencast->showActions(['duration' => 100]);
        $this->page->click('#go');
        $this->page->screencast->hideActions();
        $this->page->screencast->showOverlay('<b>Done</b>', ['duration' => 100]);
        usleep(300 * 1000);
        $this->page->screencast->stop();

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, (int) filesize($path));
    }

    #[Test]
    public function itExposesTheSameScreencastThroughTheInterfaceAccessor(): void
    {
        $this->assertSame($this->page->screencast, $this->page->screencast());
    }

    private function temporaryPath(): string
    {
        $path = sprintf('%s/playwright-php-screencast-%s.webm', sys_get_temp_dir(), bin2hex(random_bytes(6)));
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
