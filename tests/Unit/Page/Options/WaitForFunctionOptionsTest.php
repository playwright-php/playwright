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
use Playwright\Page\Options\WaitForFunctionOptions;

#[CoversClass(WaitForFunctionOptions::class)]
final class WaitForFunctionOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new WaitForFunctionOptions(timeout: 5000.0, polling: 100.0);
        $this->assertSame($options, WaitForFunctionOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = WaitForFunctionOptions::from(['timeout' => 5000.0, 'polling' => 100.0]);
        $this->assertSame(5000.0, $options->timeout);
        $this->assertSame(100.0, $options->polling);
    }

    public function testItCreatesFromArrayWithRaf(): void
    {
        $options = WaitForFunctionOptions::from(['polling' => 'raf']);
        $this->assertSame('raf', $options->polling);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        WaitForFunctionOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new WaitForFunctionOptions(
            timeout: 5000.0,
            polling: 100.0
        );

        $expected = [
            'timeout' => 5000.0,
            'polling' => 100.0,
        ];

        $this->assertSame($expected, $options->toArray());
    }

    public function testToReturnArrayWithRaf(): void
    {
        $options = new WaitForFunctionOptions(
            polling: 'raf'
        );

        $expected = [
            'polling' => 'raf',
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
