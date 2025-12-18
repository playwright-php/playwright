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
use Playwright\Page\Options\WaitForSelectorOptions;

#[CoversClass(WaitForSelectorOptions::class)]
final class WaitForSelectorOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new WaitForSelectorOptions(state: 'visible');
        $this->assertSame($options, WaitForSelectorOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = WaitForSelectorOptions::from(['state' => 'visible', 'timeout' => 5000.0]);
        $this->assertSame('visible', $options->state);
        $this->assertSame(5000.0, $options->timeout);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        WaitForSelectorOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new WaitForSelectorOptions(
            state: 'visible',
            timeout: 5000.0,
            strict: true
        );

        $expected = [
            'state' => 'visible',
            'timeout' => 5000.0,
            'strict' => true,
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
