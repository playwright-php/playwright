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
use Playwright\Page\Options\ClickOptions;

#[CoversClass(ClickOptions::class)]
final class ClickOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new ClickOptions(clickCount: 2);
        $this->assertSame($options, ClickOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = ClickOptions::from(['clickCount' => 2, 'delay' => 100.0]);
        $this->assertSame(2, $options->clickCount);
        $this->assertSame(100.0, $options->delay);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        ClickOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new ClickOptions(
            button: 'right',
            clickCount: 2,
            delay: 100.0,
            position: ['x' => 10, 'y' => 20],
            modifiers: ['Alt'],
            force: true,
            noWaitAfter: true,
            timeout: 5000.0,
            trial: true,
            strict: true
        );

        $expected = [
            'button' => 'right',
            'clickCount' => 2,
            'delay' => 100.0,
            'position' => ['x' => 10, 'y' => 20],
            'modifiers' => ['Alt'],
            'force' => true,
            'noWaitAfter' => true,
            'timeout' => 5000.0,
            'trial' => true,
            'strict' => true,
        ];

        $this->assertSame($expected, $options->toArray());
    }

    public function testToReturnArrayWithNulls(): void
    {
        $options = new ClickOptions(clickCount: 1);
        $this->assertSame(['clickCount' => 1], $options->toArray());
    }
}
