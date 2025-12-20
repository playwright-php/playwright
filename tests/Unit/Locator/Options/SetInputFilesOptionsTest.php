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
use Playwright\Locator\Options\SetInputFilesOptions;

#[CoversClass(SetInputFilesOptions::class)]
final class SetInputFilesOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new SetInputFilesOptions(
            noWaitAfter: true,
            timeout: 5000.0,
        );

        $this->assertTrue($options->noWaitAfter);
        $this->assertEquals(5000.0, $options->timeout);

        $this->assertEquals([
            'noWaitAfter' => true,
            'timeout' => 5000.0,
        ], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = SetInputFilesOptions::from([
            'noWaitAfter' => false,
            'timeout' => 1000.0,
        ]);

        $this->assertFalse($options->noWaitAfter);
        $this->assertEquals(1000.0, $options->timeout);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new SetInputFilesOptions(timeout: 50.0);
        $options = SetInputFilesOptions::from($original);

        $this->assertSame($original, $options);
    }
}
