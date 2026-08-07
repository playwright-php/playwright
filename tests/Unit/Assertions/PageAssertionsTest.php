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
use Playwright\Assertions\PageAssertions;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;
use Playwright\Tracing\TracingInterface;

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
}
