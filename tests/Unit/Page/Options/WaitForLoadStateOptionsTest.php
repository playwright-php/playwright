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

namespace Playwright\Tests\Unit\Page\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Page\Options\WaitForLoadStateOptions;

#[CoversClass(WaitForLoadStateOptions::class)]
final class WaitForLoadStateOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new WaitForLoadStateOptions(timeout: 5000.0);
        $this->assertSame($options, WaitForLoadStateOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = WaitForLoadStateOptions::from(['timeout' => 5000.0]);
        $this->assertSame(5000.0, $options->timeout);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        WaitForLoadStateOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new WaitForLoadStateOptions(
            timeout: 5000.0
        );

        $expected = [
            'timeout' => 5000.0,
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
