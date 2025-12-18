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
use Playwright\Locator\Options\FillOptions;

#[CoversClass(FillOptions::class)]
final class FillOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = FillOptions::from([
            'force' => true,
            'noWaitAfter' => false,
            'timeout' => 3000.0,
        ]);

        $result = $options->toArray();

        $this->assertTrue($result['force']);
        $this->assertFalse($result['noWaitAfter']);
        $this->assertSame(3000.0, $result['timeout']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = FillOptions::from([
            'force' => true,
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('force', $result);
        $this->assertArrayNotHasKey('noWaitAfter', $result);
        $this->assertArrayNotHasKey('timeout', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new FillOptions(force: true);
        $result = FillOptions::from($original);

        $this->assertSame($original, $result);
    }
}
