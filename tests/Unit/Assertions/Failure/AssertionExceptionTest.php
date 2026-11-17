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

namespace Playwright\Tests\Unit\Assertions\Failure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Assertions\Failure\AssertionException;

#[CoversClass(AssertionException::class)]
final class AssertionExceptionTest extends TestCase
{
    public function testKeepsTheActualAndExpectedValues(): void
    {
        $exception = new AssertionException('Expected locator to be visible.', actual: false, expected: true);

        $this->assertSame('Expected locator to be visible.', $exception->getMessage());
        $this->assertFalse($exception->actual);
        $this->assertTrue($exception->expected);
        $this->assertNull($exception->getPrevious());
    }

    public function testKeepsThePreviousError(): void
    {
        $previous = new \RuntimeException('strict mode violation');

        $exception = new AssertionException('Expected locator to be visible.', previous: $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
