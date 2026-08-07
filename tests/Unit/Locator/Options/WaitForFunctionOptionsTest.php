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

namespace Playwright\Tests\Unit\Locator\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\Options\WaitForFunctionOptions;

#[CoversClass(WaitForFunctionOptions::class)]
final class WaitForFunctionOptionsTest extends TestCase
{
    public function testToArrayIncludesTheTimeout(): void
    {
        $options = WaitForFunctionOptions::from(['timeout' => 750.0]);

        $this->assertSame(['timeout' => 750.0], $options->toArray());
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $this->assertSame([], (new WaitForFunctionOptions())->toArray());
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new WaitForFunctionOptions(timeout: 100.0);

        $this->assertSame($original, WaitForFunctionOptions::from($original));
    }
}
