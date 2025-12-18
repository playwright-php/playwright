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
use Playwright\Page\Options\SetContentOptions;

#[CoversClass(SetContentOptions::class)]
final class SetContentOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new SetContentOptions(waitUntil: 'load');
        $this->assertSame($options, SetContentOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = SetContentOptions::from(['waitUntil' => 'load', 'timeout' => 5000.0]);
        $this->assertSame('load', $options->waitUntil);
        $this->assertSame(5000.0, $options->timeout);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        SetContentOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new SetContentOptions(
            waitUntil: 'networkidle',
            timeout: 5000.0
        );

        $expected = [
            'waitUntil' => 'networkidle',
            'timeout' => 5000.0,
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
