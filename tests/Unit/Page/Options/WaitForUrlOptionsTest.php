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
use Playwright\Page\Options\WaitForUrlOptions;

#[CoversClass(WaitForUrlOptions::class)]
final class WaitForUrlOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new WaitForUrlOptions(timeout: 5000.0);
        $this->assertSame($options, WaitForUrlOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = WaitForUrlOptions::from(['timeout' => 5000.0, 'waitUntil' => 'load']);
        $this->assertSame(5000.0, $options->timeout);
        $this->assertSame('load', $options->waitUntil);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        WaitForUrlOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new WaitForUrlOptions(
            timeout: 5000.0,
            waitUntil: 'networkidle'
        );

        $expected = [
            'timeout' => 5000.0,
            'waitUntil' => 'networkidle',
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
