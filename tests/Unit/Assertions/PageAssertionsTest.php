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

namespace Playwright\Tests\Unit\Assertions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Assertions\AssertionOptions;
use Playwright\Assertions\Failure\AssertionException;
use Playwright\Assertions\Internal\ScreenshotExpectation;
use Playwright\Assertions\Options\ToHaveScreenshotOptions;
use Playwright\Assertions\PageAssertions;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\Page;
use Playwright\Page\PageInterface;
use Playwright\Tracing\TracingInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(PageAssertions::class)]
final class PageAssertionsTest extends TestCase
{
    public function testToHaveTitleRetriesAndUsesTracing(): void
    {
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->exactly(2))->method('title')->willReturn('Loading', 'Ready');

        $tracing = $this->createMock(TracingInterface::class);
        $tracing->expects($this->once())->method('group')->with('expect(page).toHaveTitle');
        $tracing->expects($this->once())->method('groupEnd');

        $assertions = new PageAssertions($page, $tracing);

        $this->assertSame($assertions, $assertions->withTimeout(100)->withPollInterval(0)->toHaveTitle('Ready'));
    }

    public function testToHaveUrlSupportsNegation(): void
    {
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->once())->method('url')->willReturn('https://example.com/');

        $assertions = new PageAssertions($page);

        $this->assertSame($assertions, $assertions->not()->toHaveURL('https://example.com/login'));
    }

    public function testFailureUsesAssertionOptionsAndResetsNegation(): void
    {
        $page = $this->createMock(PageInterface::class);
        $page->expects($this->exactly(3))->method('title')->willReturn('Actual');
        $assertions = new PageAssertions($page);

        try {
            $assertions->not()->toHaveTitle('Actual', new AssertionOptions(timeoutMs: 0, message: 'Custom failure'));
            $this->fail('Expected the negated assertion to fail.');
        } catch (AssertionException $exception) {
            $this->assertSame('Custom failure', $exception->getMessage());
            $this->assertSame('Actual', $exception->actual);
            $this->assertSame('Actual', $exception->expected);
        }

        $this->assertSame($assertions, $assertions->toHaveTitle('Actual'));
    }

    public function testToMatchAriaSnapshotSnapshotsTheBody(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('ariaSnapshot')->willReturn("- heading \"One\" [level=1]\n");

        $page = $this->createMock(PageInterface::class);
        $page->expects($this->once())->method('locator')->with('body')->willReturn($locator);

        $assertions = new PageAssertions($page);

        $this->assertSame($assertions, $assertions->toMatchAriaSnapshot('- heading "One" [level=1]'));
    }

    public function testToMatchAriaSnapshotFailsOnADifferentTree(): void
    {
        $assertions = new PageAssertions($this->pageSnapshotting('- list:'));

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('Expected page to match the ARIA snapshot.');

        $assertions->toMatchAriaSnapshot('- heading "One"', new AssertionOptions(timeoutMs: 0));
    }

    public function testToMatchAriaSnapshotUsesTheConfiguredFailureMessage(): void
    {
        $assertions = new PageAssertions($this->pageSnapshotting('- list:'));

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('No heading on the page.');

        $assertions->toMatchAriaSnapshot('- heading "One"', new AssertionOptions(timeoutMs: 0, message: 'No heading on the page.'));
    }

    public function testNegatedToMatchAriaSnapshotFailsOnAMatchingTree(): void
    {
        $assertions = new PageAssertions($this->pageSnapshotting('- list:'));

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('Expected page not to match the ARIA snapshot.');

        $assertions->not()->toMatchAriaSnapshot('- list:', new AssertionOptions(timeoutMs: 0));
    }

    public function testNegatedToMatchAriaSnapshotPassesOnADifferentTree(): void
    {
        $assertions = new PageAssertions($this->pageSnapshotting('- list:'));

        $this->assertSame($assertions, $assertions->not()->toMatchAriaSnapshot('- heading "One"', new AssertionOptions(timeoutMs: 0)));
    }

    private function pageSnapshotting(string $snapshot): PageInterface
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('ariaSnapshot')->willReturn($snapshot);

        $page = $this->createMock(PageInterface::class);
        $page->method('locator')->willReturn($locator);

        return $page;
    }

    /** @var list<string> */
    private array $dirs = [];

    public function testToHaveScreenshotNeedsAPlaywrightPage(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('toHaveScreenshot() needs a Playwright\Page\Page created by Playwright PHP');

        (new PageAssertions($this->createStub(PageInterface::class)))->toHaveScreenshot('/tmp/page.png');
    }

    public function testToHaveScreenshotSendsPageOptionsInsideATracingGroup(): void
    {
        $path = $this->snapshotDir().'/page.png';
        file_put_contents($path, 'baseline');

        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (array $payload): bool => 'page.expectScreenshot' === $payload['action']
                && true === $payload['options']['fullPage']
                && ['x' => 0, 'y' => 0, 'width' => 320, 'height' => 200] === $payload['options']['clip']))
            ->willReturn(['errorMessage' => null]);

        $tracing = $this->createMock(TracingInterface::class);
        $tracing->expects($this->once())->method('group')->with('expect(page).toHaveScreenshot');
        $tracing->expects($this->once())->method('groupEnd');

        $page = new Page($transport, $this->createStub(BrowserContextInterface::class), 'page-id', new PlaywrightConfig());
        $assertions = new PageAssertions($page, $tracing);

        $this->assertSame($assertions, $assertions->toHaveScreenshot($path, new ToHaveScreenshotOptions(
            fullPage: true,
            clip: ['x' => 0, 'y' => 0, 'width' => 320, 'height' => 200],
        )));
    }

    public function testToHaveScreenshotClosesTheTracingGroupOnFailure(): void
    {
        $path = $this->snapshotDir().'/page.png';
        file_put_contents($path, 'baseline');
        $_SERVER[ScreenshotExpectation::UPDATE_ENV] = 'none';

        $transport = $this->createStub(TransportInterface::class);
        $transport->method('send')->willReturn(['actual' => base64_encode('actual'), 'errorMessage' => 'different']);

        $tracing = $this->createMock(TracingInterface::class);
        $tracing->expects($this->once())->method('groupEnd');

        $page = new Page($transport, $this->createStub(BrowserContextInterface::class), 'page-id', new PlaywrightConfig());

        $this->expectException(AssertionException::class);

        try {
            (new PageAssertions($page, $tracing))->toHaveScreenshot($path);
        } finally {
            foreach (glob(getcwd().'/test-failures/snapshots/page-*', GLOB_ONLYDIR) ?: [] as $directory) {
                foreach (glob($directory.'/*') ?: [] as $file) {
                    unlink($file);
                }
                rmdir($directory);
            }
            @rmdir(getcwd().'/test-failures/snapshots');
        }
    }

    private function snapshotDir(): string
    {
        $dir = sys_get_temp_dir().'/pw-assertions-'.bin2hex(random_bytes(6));
        mkdir($dir);
        $this->dirs[] = $dir;

        return $dir;
    }

    protected function tearDown(): void
    {
        unset($_SERVER[ScreenshotExpectation::UPDATE_ENV]);
        foreach ($this->dirs as $dir) {
            foreach (glob($dir.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($dir);
        }
    }
}
