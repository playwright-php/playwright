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
use Playwright\Locator\Options\UncheckOptions;

#[CoversClass(UncheckOptions::class)]
final class UncheckOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new UncheckOptions(
            position: ['x' => 10, 'y' => 20],
            force: true,
            noWaitAfter: true,
            timeout: 5000.0,
            trial: true,
        );

        $this->assertEquals(['x' => 10, 'y' => 20], $options->position);
        $this->assertTrue($options->force);
        $this->assertTrue($options->noWaitAfter);
        $this->assertEquals(5000.0, $options->timeout);
        $this->assertTrue($options->trial);

        $this->assertEquals([
            'position' => ['x' => 10, 'y' => 20],
            'force' => true,
            'noWaitAfter' => true,
            'timeout' => 5000.0,
            'trial' => true,
        ], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = UncheckOptions::from([
            'position' => ['x' => 30, 'y' => 40],
            'force' => false,
            'noWaitAfter' => false,
            'timeout' => 1000.0,
            'trial' => false,
        ]);

        $this->assertEquals(['x' => 30, 'y' => 40], $options->position);
        $this->assertFalse($options->force);
        $this->assertFalse($options->noWaitAfter);
        $this->assertEquals(1000.0, $options->timeout);
        $this->assertFalse($options->trial);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new UncheckOptions(force: true);
        $options = UncheckOptions::from($original);

        $this->assertSame($original, $options);
    }
}
