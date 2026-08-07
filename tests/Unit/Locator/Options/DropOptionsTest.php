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
use Playwright\Locator\Options\DropOptions;

#[CoversClass(DropOptions::class)]
final class DropOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = DropOptions::from([
            'position' => ['x' => 10.0, 'y' => 20.0],
            'timeout' => 7000.0,
        ]);

        $result = $options->toArray();

        $this->assertSame(['x' => 10.0, 'y' => 20.0], $result['position']);
        $this->assertSame(7000.0, $result['timeout']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $result = DropOptions::from([])->toArray();

        $this->assertSame([], $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new DropOptions(position: ['x' => 1.0, 'y' => 2.0]);

        $this->assertSame($original, DropOptions::from($original));
    }
}
