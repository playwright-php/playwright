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
use Playwright\Locator\Options\CheckOptions;

#[CoversClass(CheckOptions::class)]
final class CheckOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = CheckOptions::from([
            'position' => ['x' => 5.0, 'y' => 10.0],
            'force' => true,
            'noWaitAfter' => false,
            'timeout' => 4000.0,
            'trial' => true,
        ]);

        $result = $options->toArray();

        $this->assertSame(['x' => 5.0, 'y' => 10.0], $result['position']);
        $this->assertTrue($result['force']);
        $this->assertFalse($result['noWaitAfter']);
        $this->assertSame(4000.0, $result['timeout']);
        $this->assertTrue($result['trial']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = CheckOptions::from([
            'force' => true,
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('force', $result);
        $this->assertArrayNotHasKey('position', $result);
        $this->assertArrayNotHasKey('noWaitAfter', $result);
        $this->assertArrayNotHasKey('timeout', $result);
        $this->assertArrayNotHasKey('trial', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new CheckOptions(force: true);
        $result = CheckOptions::from($original);

        $this->assertSame($original, $result);
    }
}
