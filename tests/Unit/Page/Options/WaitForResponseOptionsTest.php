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

namespace Playwright\Tests\Unit\Page\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Page\Options\WaitForResponseOptions;

#[CoversClass(WaitForResponseOptions::class)]
final class WaitForResponseOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new WaitForResponseOptions(timeout: 1000.0);

        $this->assertSame(1000.0, $options->timeout);
        $this->assertEquals(['timeout' => 1000.0], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = WaitForResponseOptions::from(['timeout' => 1000.0]);

        $this->assertSame(1000.0, $options->timeout);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new WaitForResponseOptions(timeout: 1000.0);
        $options = WaitForResponseOptions::from($original);

        $this->assertSame($original, $options);
    }
}
