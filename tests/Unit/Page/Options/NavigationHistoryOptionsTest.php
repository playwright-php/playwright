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
use Playwright\Page\Options\NavigationHistoryOptions;

#[CoversClass(NavigationHistoryOptions::class)]
final class NavigationHistoryOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new NavigationHistoryOptions(timeout: 5000.0);
        $this->assertSame($options, NavigationHistoryOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = NavigationHistoryOptions::from(['timeout' => 5000.0, 'waitUntil' => 'load']);
        $this->assertSame(5000.0, $options->timeout);
        $this->assertSame('load', $options->waitUntil);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        NavigationHistoryOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new NavigationHistoryOptions(
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
