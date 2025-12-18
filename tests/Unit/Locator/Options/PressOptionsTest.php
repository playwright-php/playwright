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
use Playwright\Locator\Options\PressOptions;

#[CoversClass(PressOptions::class)]
final class PressOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = PressOptions::from([
            'delay' => 50.0,
            'noWaitAfter' => true,
            'timeout' => 2000.0,
        ]);

        $result = $options->toArray();

        $this->assertSame(50.0, $result['delay']);
        $this->assertTrue($result['noWaitAfter']);
        $this->assertSame(2000.0, $result['timeout']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = PressOptions::from([
            'delay' => 100.0,
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('delay', $result);
        $this->assertArrayNotHasKey('noWaitAfter', $result);
        $this->assertArrayNotHasKey('timeout', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new PressOptions(delay: 75.0);
        $result = PressOptions::from($original);

        $this->assertSame($original, $result);
    }
}
