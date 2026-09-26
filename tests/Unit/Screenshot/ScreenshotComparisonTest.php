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

namespace Playwright\Tests\Unit\Screenshot;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Screenshot\ScreenshotComparison;

#[CoversClass(ScreenshotComparison::class)]
final class ScreenshotComparisonTest extends TestCase
{
    public function testDecodesTheBridgeResponse(): void
    {
        $comparison = ScreenshotComparison::fromResponse([
            'actual' => base64_encode('actual'),
            'previous' => base64_encode('previous'),
            'diff' => base64_encode('diff'),
            'errorMessage' => '3 pixels are different.',
            'timedOut' => false,
        ]);

        $this->assertSame('actual', $comparison->actual);
        $this->assertSame('previous', $comparison->previous);
        $this->assertSame('diff', $comparison->diff);
        $this->assertSame('3 pixels are different.', $comparison->errorMessage);
        $this->assertFalse($comparison->timedOut);
        $this->assertFalse($comparison->matches());
    }

    public function testMatchesWhenTheBridgeReportsNoError(): void
    {
        $comparison = ScreenshotComparison::fromResponse(['actual' => null, 'diff' => null, 'errorMessage' => null, 'timedOut' => false]);

        $this->assertTrue($comparison->matches());
        $this->assertNull($comparison->actual);
    }

    public function testIgnoresMalformedValues(): void
    {
        $comparison = ScreenshotComparison::fromResponse(['actual' => '***', 'errorMessage' => '', 'timedOut' => 'yes']);

        $this->assertNull($comparison->actual);
        $this->assertNull($comparison->errorMessage);
        $this->assertFalse($comparison->timedOut);
    }
}
