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

namespace Playwright\Tests\Unit\Testing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Assertions\Failure\AssertionException;
use Playwright\Locator\LocatorInterface;
use Playwright\Testing\Expect;
use Playwright\Tracing\TracingInterface;

#[CoversClass(Expect::class)]
final class ExpectTraceGroupTest extends TestCase
{
    private function createVisibleLocator(): LocatorInterface
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('isVisible')->willReturn(true);
        $locator->method('getSelector')->willReturn('#heading');

        return $locator;
    }

    public function testAssertionIsWrappedInATracingGroup(): void
    {
        $tracing = $this->createMock(TracingInterface::class);
        $tracing->expects($this->once())
            ->method('group')
            ->with('expect(#heading).toBeVisible');
        $tracing->expects($this->once())
            ->method('groupEnd');

        (new Expect($this->createVisibleLocator(), $tracing))->toBeVisible();
    }

    public function testNegatedAssertionGroupNameContainsNot(): void
    {
        $tracing = $this->createMock(TracingInterface::class);
        $tracing->expects($this->once())
            ->method('group')
            ->with('expect(#heading).not.toBeHidden');
        $tracing->expects($this->once())
            ->method('groupEnd');

        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('isVisible')->willReturn(true);
        $locator->method('getSelector')->willReturn('#heading');

        (new Expect($locator, $tracing))->not()->toBeHidden();
    }

    public function testGroupIsClosedWhenTheAssertionFails(): void
    {
        $tracing = $this->createMock(TracingInterface::class);
        $tracing->expects($this->once())->method('group');
        $tracing->expects($this->once())->method('groupEnd');

        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('isVisible')->willReturn(false);
        $locator->method('getSelector')->willReturn('#heading');

        $expect = new Expect($locator, $tracing);
        $expect->withTimeout(50)->withPollInterval(10);

        try {
            $expect->toBeVisible();
            self::fail('Expected an AssertionException');
        } catch (AssertionException) {
        }
    }

    public function testNoTracingMeansNoGroups(): void
    {
        (new Expect($this->createVisibleLocator()))->toBeVisible();

        self::assertTrue(true);
    }
}
