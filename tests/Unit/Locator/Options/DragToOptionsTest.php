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
use Playwright\Locator\Options\DragToOptions;

#[CoversClass(DragToOptions::class)]
final class DragToOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = DragToOptions::from([
            'sourcePosition' => ['x' => 10.0, 'y' => 20.0],
            'targetPosition' => ['x' => 30.0, 'y' => 40.0],
            'force' => true,
            'noWaitAfter' => false,
            'steps' => 10,
            'timeout' => 7000.0,
            'trial' => true,
        ]);

        $result = $options->toArray();

        $this->assertSame(['x' => 10.0, 'y' => 20.0], $result['sourcePosition']);
        $this->assertSame(['x' => 30.0, 'y' => 40.0], $result['targetPosition']);
        $this->assertTrue($result['force']);
        $this->assertFalse($result['noWaitAfter']);
        $this->assertSame(10, $result['steps']);
        $this->assertSame(7000.0, $result['timeout']);
        $this->assertTrue($result['trial']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = DragToOptions::from([
            'sourcePosition' => ['x' => 5.0, 'y' => 5.0],
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('sourcePosition', $result);
        $this->assertArrayNotHasKey('targetPosition', $result);
        $this->assertArrayNotHasKey('force', $result);
        $this->assertArrayNotHasKey('noWaitAfter', $result);
        $this->assertArrayNotHasKey('steps', $result);
        $this->assertArrayNotHasKey('timeout', $result);
        $this->assertArrayNotHasKey('trial', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new DragToOptions(sourcePosition: ['x' => 1.0, 'y' => 2.0]);
        $result = DragToOptions::from($original);

        $this->assertSame($original, $result);
    }
}
