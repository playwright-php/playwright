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
use Playwright\Locator\Options\DblClickOptions;

#[CoversClass(DblClickOptions::class)]
final class DblClickOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = DblClickOptions::from([
            'button' => 'right',
            'delay' => 100.5,
            'modifiers' => ['Shift', 'Control'],
            'position' => ['x' => 10.0, 'y' => 20.0],
            'force' => true,
            'noWaitAfter' => false,
            'steps' => 5,
            'timeout' => 5000.0,
            'trial' => true,
        ]);

        $result = $options->toArray();

        $this->assertSame('right', $result['button']);
        $this->assertSame(100.5, $result['delay']);
        $this->assertSame(['Shift', 'Control'], $result['modifiers']);
        $this->assertSame(['x' => 10.0, 'y' => 20.0], $result['position']);
        $this->assertTrue($result['force']);
        $this->assertFalse($result['noWaitAfter']);
        $this->assertSame(5, $result['steps']);
        $this->assertSame(5000.0, $result['timeout']);
        $this->assertTrue($result['trial']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = DblClickOptions::from([
            'button' => 'left',
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('button', $result);
        $this->assertArrayNotHasKey('delay', $result);
        $this->assertArrayNotHasKey('modifiers', $result);
        $this->assertArrayNotHasKey('position', $result);
        $this->assertArrayNotHasKey('force', $result);
        $this->assertArrayNotHasKey('noWaitAfter', $result);
        $this->assertArrayNotHasKey('steps', $result);
        $this->assertArrayNotHasKey('timeout', $result);
        $this->assertArrayNotHasKey('trial', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new DblClickOptions(button: 'middle');
        $result = DblClickOptions::from($original);

        $this->assertSame($original, $result);
    }
}
