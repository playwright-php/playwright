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
use Playwright\WebSocket\Options\WaitForEventOptions;

#[CoversClass(WaitForEventOptions::class)]
final class WaitForEventOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $predicate = fn () => true;
        $options = new WaitForEventOptions(predicate: $predicate, timeout: 1000.0);

        $this->assertSame($predicate, $options->predicate);
        $this->assertSame(1000.0, $options->timeout);
        $this->assertEquals(['predicate' => $predicate, 'timeout' => 1000.0], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $predicate = fn () => true;
        $options = WaitForEventOptions::from(['predicate' => $predicate, 'timeout' => 1000.0]);

        $this->assertSame($predicate, $options->predicate);
        $this->assertSame(1000.0, $options->timeout);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new WaitForEventOptions(timeout: 1000.0);
        $options = WaitForEventOptions::from($original);

        $this->assertSame($original, $options);
    }
}
