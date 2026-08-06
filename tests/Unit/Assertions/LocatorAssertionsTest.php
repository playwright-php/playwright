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

        self::assertSame($assertions, $assertions->toBeAttached());
    }

    public function testToBeEditable(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('isEditable')->willReturn(true);

        $assertions = new LocatorAssertions($locator);

        self::assertSame($assertions, $assertions->toBeEditable());
    }

    public function testToBeAttachedResetsNegationAfterFailure(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->exactly(2))->method('isAttached')->willReturn(true);
        $assertions = new LocatorAssertions($locator);

        try {
            $assertions->not()->toBeAttached();
            self::fail('Expected the negated assertion to fail.');
        } catch (AssertionException $exception) {
            self::assertSame('Expected locator to be detached.', $exception->getMessage());
        }

        self::assertSame($assertions, $assertions->toBeAttached());
    }

    public function testToBeEditableResetsNegationAfterFailure(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->exactly(2))->method('isEditable')->willReturn(true);
        $assertions = new LocatorAssertions($locator);

        try {
            $assertions->not()->toBeEditable();
            self::fail('Expected the negated assertion to fail.');
        } catch (AssertionException $exception) {
            self::assertSame('Expected locator not to be editable.', $exception->getMessage());
        }

        self::assertSame($assertions, $assertions->toBeEditable());
    }
}
