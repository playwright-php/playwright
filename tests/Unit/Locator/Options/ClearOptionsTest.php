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
use Playwright\Locator\Options\ClearOptions;

#[CoversClass(ClearOptions::class)]
final class ClearOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = ClearOptions::from([
            'force' => false,
            'noWaitAfter' => true,
            'timeout' => 6500.0,
        ]);

        $result = $options->toArray();

        $this->assertFalse($result['force']);
        $this->assertTrue($result['noWaitAfter']);
        $this->assertSame(6500.0, $result['timeout']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = ClearOptions::from([
            'noWaitAfter' => true,
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('noWaitAfter', $result);
        $this->assertArrayNotHasKey('force', $result);
        $this->assertArrayNotHasKey('timeout', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new ClearOptions(force: false);
        $result = ClearOptions::from($original);

        $this->assertSame($original, $result);
    }
}
