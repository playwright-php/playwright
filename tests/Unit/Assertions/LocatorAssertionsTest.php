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
use Playwright\Assertions\LocatorAssertions;
use Playwright\Locator\LocatorInterface;

#[CoversClass(LocatorAssertions::class)]
final class LocatorAssertionsTest extends TestCase
{
    public function testToBeAttached(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('isAttached')->willReturn(true);

        $assertions = new LocatorAssertions($locator);

        $this->assertSame($assertions, $assertions->toBeAttached());
    }

    public function testToBeEditable(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('isEditable')->willReturn(true);

        $assertions = new LocatorAssertions($locator);

        $this->assertSame($assertions, $assertions->toBeEditable());
    }

    public function testToBeAttachedResetsNegationAfterFailure(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->exactly(2))->method('isAttached')->willReturn(true);
        $assertions = new LocatorAssertions($locator);

        try {
            $assertions->not()->toBeAttached();
            $this->fail('Expected the negated assertion to fail.');
        } catch (AssertionException $exception) {
            $this->assertSame('Expected locator to be detached.', $exception->getMessage());
        }

        $this->assertSame($assertions, $assertions->toBeAttached());
    }

    public function testToBeEditableResetsNegationAfterFailure(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->exactly(2))->method('isEditable')->willReturn(true);
        $assertions = new LocatorAssertions($locator);

        try {
            $assertions->not()->toBeEditable();
            $this->fail('Expected the negated assertion to fail.');
        } catch (AssertionException $exception) {
            $this->assertSame('Expected locator not to be editable.', $exception->getMessage());
        }

        $this->assertSame($assertions, $assertions->toBeEditable());
    }

    public function testToBeInViewportUsesConfiguredRatio(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), 0.5)
            ->willReturn(true);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toBeInViewport(new AssertionOptions(ratio: 0.5)));
    }

    public function testToHaveJavaScriptPropertyUsesDeepNativeComparison(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), ['name' => 'state', 'expected' => ['ready' => true]])
            ->willReturn(true);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toHaveJSProperty('state', ['ready' => true]));
    }

    public function testToHaveValuesNormalizesASingleExpectedValue(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'))
            ->willReturn(['second']);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toHaveValues('second'));
    }

    public function testToHaveRoleRespectsNegationAndTimeout(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'))
            ->willReturn('button');

        $this->assertInstanceOf(
            LocatorAssertions::class,
            (new LocatorAssertions($locator))->not()->toHaveRole('link', new AssertionOptions(timeoutMs: 0))
        );
    }

    public function testToContainClassEvaluatesEachClassToken(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), ['primary', 'active'])
            ->willReturn(true);

        $this->assertInstanceOf(LocatorAssertions::class, (new LocatorAssertions($locator))->toContainClass('primary active'));
    }

    public function testToContainClassRejectsAnEmptyClassList(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new LocatorAssertions($this->createMock(LocatorInterface::class)))->toContainClass('   ');
    }

    public function testToBeInViewportDefaultsToAnyVisiblePart(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())
            ->method('evaluate')
            ->with($this->isType('string'), 0.0)
            ->willReturn(true);

        $assertions = new LocatorAssertions($locator);

        $this->assertSame($assertions, $assertions->toBeInViewport());
    }

    public function testToBeInViewportRejectsARatioAboveOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Viewport ratio must be between 0 and 1.');

        (new LocatorAssertions($this->createMock(LocatorInterface::class)))
            ->toBeInViewport(new AssertionOptions(ratio: 1.5));
    }

    public function testToBeInViewportRejectsANegativeRatio(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Viewport ratio must be between 0 and 1.');

        (new LocatorAssertions($this->createMock(LocatorInterface::class)))
            ->toBeInViewport(new AssertionOptions(ratio: -0.1));
    }
}
