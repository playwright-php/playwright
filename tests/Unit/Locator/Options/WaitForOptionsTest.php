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
use Playwright\Locator\Options\WaitForOptions;

#[CoversClass(WaitForOptions::class)]
final class WaitForOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = WaitForOptions::from([
            'state' => 'visible',
            'timeout' => 9000.0,
        ]);

        $result = $options->toArray();

        $this->assertSame('visible', $result['state']);
        $this->assertSame(9000.0, $result['timeout']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = WaitForOptions::from([
            'state' => 'hidden',
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('state', $result);
        $this->assertArrayNotHasKey('timeout', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new WaitForOptions(state: 'attached');
        $result = WaitForOptions::from($original);

        $this->assertSame($original, $result);
    }
}
