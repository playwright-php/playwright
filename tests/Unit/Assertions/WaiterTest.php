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
use Playwright\Assertions\Internal\Waiter;
use Playwright\Exception\TimeoutException;

#[CoversClass(Waiter::class)]
final class WaiterTest extends TestCase
{
    public function testItReturnsAsSoonAsThePredicateHolds(): void
    {
        $calls = 0;

        Waiter::eventually(function () use (&$calls): bool {
            ++$calls;

            return true;
        }, 1000, 10);

        $this->assertSame(1, $calls);
    }

    public function testItRetriesUntilThePredicateHolds(): void
    {
        $calls = 0;

        Waiter::eventually(function () use (&$calls): bool {
            ++$calls;

            return $calls >= 3;
        }, 1000, 1);

        $this->assertSame(3, $calls);
    }

    public function testItThrowsOnceTheDeadlinePasses(): void
    {
        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Condition not met within timeout.');

        Waiter::eventually(static fn (): bool => false, 20, 1);
    }

    public function testItEvaluatesThePredicateAtLeastOnceWithAZeroTimeout(): void
    {
        $calls = 0;

        try {
            Waiter::eventually(function () use (&$calls): bool {
                ++$calls;

                return false;
            }, 0, 1);
        } catch (TimeoutException) {
        }

        $this->assertSame(1, $calls);
    }
}
