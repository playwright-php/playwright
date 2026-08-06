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
use Playwright\Locator\Options\AriaSnapshotOptions;
use Playwright\Locator\Options\BoundingBoxOptions;
use Playwright\Locator\Options\DispatchEventOptions;
use Playwright\Locator\Options\SelectTextOptions;
use Playwright\Locator\Options\SetCheckedOptions;
use Playwright\Locator\Options\TapOptions;

#[CoversClass(AriaSnapshotOptions::class)]
#[CoversClass(BoundingBoxOptions::class)]
#[CoversClass(DispatchEventOptions::class)]
#[CoversClass(SelectTextOptions::class)]
#[CoversClass(SetCheckedOptions::class)]
#[CoversClass(TapOptions::class)]
final class DirectLocatorOptionsTest extends TestCase
{
    public function testSingleTimeoutOptionsRoundTrip(): void
    {
        $this->assertSame(['timeout' => 500.0], AriaSnapshotOptions::from(['timeout' => 500.0])->toArray());
        $this->assertSame(['timeout' => 500.0], BoundingBoxOptions::from(['timeout' => 500.0])->toArray());
        $this->assertSame(['timeout' => 500.0], DispatchEventOptions::from(['timeout' => 500.0])->toArray());
    }

    public function testSelectTextOptionsRoundTrip(): void
    {
        $this->assertSame(
            ['force' => true, 'timeout' => 500.0],
            SelectTextOptions::from(['force' => true, 'timeout' => 500.0])->toArray()
        );
    }

    public function testSetCheckedOptionsRoundTrip(): void
    {
        $this->assertSame(
            ['position' => ['x' => 1.0, 'y' => 2.0], 'force' => true, 'noWaitAfter' => false, 'timeout' => 500.0, 'trial' => true],
            SetCheckedOptions::from([
                'position' => ['x' => 1.0, 'y' => 2.0],
                'force' => true,
                'noWaitAfter' => false,
                'timeout' => 500.0,
                'trial' => true,
            ])->toArray()
        );
    }

    public function testTapOptionsRoundTrip(): void
    {
        $this->assertSame(
            ['modifiers' => ['Shift'], 'position' => ['x' => 1.0, 'y' => 2.0], 'force' => true, 'noWaitAfter' => false, 'timeout' => 500.0, 'trial' => true],
            TapOptions::from([
                'modifiers' => ['Shift'],
                'position' => ['x' => 1.0, 'y' => 2.0],
                'force' => true,
                'noWaitAfter' => false,
                'timeout' => 500.0,
                'trial' => true,
            ])->toArray()
        );
    }
}
