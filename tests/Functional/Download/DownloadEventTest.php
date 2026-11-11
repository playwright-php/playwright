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

namespace Playwright\Tests\Functional\Download;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Download\DownloadInterface;
use Playwright\Page\Page;
use Playwright\Page\PageEventHandler;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(PageEventHandler::class)]
final class DownloadEventTest extends FunctionalTestCase
{
    public function testTheListenerReceivesEachDownload(): void
    {
        /** @var list<DownloadInterface> $downloads */
        $downloads = [];
        $this->page->events()->onDownload(static function (DownloadInterface $download) use (&$downloads): void {
            $downloads[] = $download;
        });
        $this->goto('/download.html');

        $this->page->locator('#report')->click();
        $this->page->locator('#inline')->click();
        $this->waitUntil(static fn (): bool => 2 === count($downloads));

        $this->assertSame(['report.csv', 'hello.txt'], array_map(static fn (DownloadInterface $download): string => $download->suggestedFilename(), $downloads));
        $this->assertSame($this->page, $downloads[0]->page());
        $path = $downloads[0]->path();
        $this->assertIsString($path);
        $this->assertSame("id,name\n1,Ada\n", file_get_contents($path));
    }

    public function testTheListenerAndWaitForDownloadShareTheDownload(): void
    {
        $fromEvent = null;
        $this->page->events()->onDownload(static function (DownloadInterface $download) use (&$fromEvent): void {
            $fromEvent = $download;
        });
        $this->goto('/download.html');

        $download = $this->page->waitForDownload(fn () => $this->page->locator('#report')->click());
        $this->waitUntil(static fn (): bool => null !== $fromEvent);

        $this->assertInstanceOf(DownloadInterface::class, $fromEvent);
        $this->assertSame($download->path(), $fromEvent->path());
    }

    /**
     * Page events reach PHP while it exchanges messages with the bridge, so
     * the test keeps the page busy until the listener has run.
     */
    private function waitUntil(callable $condition): void
    {
        $deadline = microtime(true) + 5;
        while (!$condition() && microtime(true) < $deadline) {
            $this->page->evaluate('1');
        }

        $this->assertTrue($condition(), 'The download event did not reach PHP in time.');
    }
}
