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
use Playwright\Page\Options\TypeOptions;

#[CoversClass(TypeOptions::class)]
final class TypeOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new TypeOptions(delay: 100.0);
        $this->assertSame($options, TypeOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = TypeOptions::from(['delay' => 100.0, 'noWaitAfter' => true]);
        $this->assertSame(100.0, $options->delay);
        $this->assertTrue($options->noWaitAfter);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        TypeOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new TypeOptions(
            delay: 100.0,
            noWaitAfter: true,
            timeout: 5000.0,
            strict: true
        );

        $expected = [
            'delay' => 100.0,
            'noWaitAfter' => true,
            'timeout' => 5000.0,
            'strict' => true,
        ];

        $this->assertSame($expected, $options->toArray());
    }
}
