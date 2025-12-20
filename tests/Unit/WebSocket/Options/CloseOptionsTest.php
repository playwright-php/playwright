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

namespace Playwright\Tests\Unit\WebSocket\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\WebSocket\Options\CloseOptions;

#[CoversClass(CloseOptions::class)]
final class CloseOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new CloseOptions(code: 1000, reason: 'Normal Closure');

        $this->assertSame(1000, $options->code);
        $this->assertSame('Normal Closure', $options->reason);
        $this->assertEquals(['code' => 1000, 'reason' => 'Normal Closure'], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = CloseOptions::from(['code' => 1000, 'reason' => 'Normal Closure']);

        $this->assertSame(1000, $options->code);
        $this->assertSame('Normal Closure', $options->reason);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new CloseOptions(code: 1000);
        $options = CloseOptions::from($original);

        $this->assertSame($original, $options);
    }
}
