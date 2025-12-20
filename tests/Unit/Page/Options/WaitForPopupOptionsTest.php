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
use Playwright\Page\Options\WaitForPopupOptions;

#[CoversClass(WaitForPopupOptions::class)]
final class WaitForPopupOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new WaitForPopupOptions(timeout: 5000.0);
        $this->assertSame($options, WaitForPopupOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $predicate = function () { return true; };
        $options = WaitForPopupOptions::from(['timeout' => 5000.0, 'predicate' => $predicate]);
        $this->assertSame(5000.0, $options->timeout);
        $this->assertSame($predicate, $options->predicate);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        WaitForPopupOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $predicate = function () { return true; };
        $options = new WaitForPopupOptions(
            predicate: $predicate,
            timeout: 5000.0
        );

        $expected = [
            'predicate' => $predicate,
            'timeout' => 5000.0,
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
