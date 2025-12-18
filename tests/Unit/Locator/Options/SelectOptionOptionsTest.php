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
use Playwright\Locator\Options\SelectOptionOptions;

#[CoversClass(SelectOptionOptions::class)]
final class SelectOptionOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = SelectOptionOptions::from([
            'force' => true,
            'noWaitAfter' => false,
            'timeout' => 5500.0,
        ]);

        $result = $options->toArray();

        $this->assertTrue($result['force']);
        $this->assertFalse($result['noWaitAfter']);
        $this->assertSame(5500.0, $result['timeout']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = SelectOptionOptions::from([
            'timeout' => 1000.0,
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('timeout', $result);
        $this->assertArrayNotHasKey('force', $result);
        $this->assertArrayNotHasKey('noWaitAfter', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new SelectOptionOptions(force: true);
        $result = SelectOptionOptions::from($original);

        $this->assertSame($original, $result);
    }
}
