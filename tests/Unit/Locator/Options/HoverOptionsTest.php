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
use Playwright\Locator\Options\HoverOptions;

#[CoversClass(HoverOptions::class)]
final class HoverOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = HoverOptions::from([
            'modifiers' => ['Alt', 'Control'],
            'position' => ['x' => 15.5, 'y' => 25.5],
            'force' => false,
            'noWaitAfter' => true,
            'timeout' => 6000.0,
            'trial' => false,
        ]);

        $result = $options->toArray();

        $this->assertSame(['Alt', 'Control'], $result['modifiers']);
        $this->assertSame(['x' => 15.5, 'y' => 25.5], $result['position']);
        $this->assertFalse($result['force']);
        $this->assertTrue($result['noWaitAfter']);
        $this->assertSame(6000.0, $result['timeout']);
        $this->assertFalse($result['trial']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = HoverOptions::from([
            'force' => true,
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('force', $result);
        $this->assertArrayNotHasKey('modifiers', $result);
        $this->assertArrayNotHasKey('position', $result);
        $this->assertArrayNotHasKey('noWaitAfter', $result);
        $this->assertArrayNotHasKey('timeout', $result);
        $this->assertArrayNotHasKey('trial', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new HoverOptions(force: true);
        $result = HoverOptions::from($original);

        $this->assertSame($original, $result);
    }
}
