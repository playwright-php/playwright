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
use Playwright\Locator\Options\GetByOptions;

#[CoversClass(GetByOptions::class)]
final class GetByOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new GetByOptions(exact: true);

        $this->assertTrue($options->exact);
        $this->assertEquals(['exact' => true], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = GetByOptions::from(['exact' => false]);

        $this->assertFalse($options->exact);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new GetByOptions(exact: true);
        $options = GetByOptions::from($original);

        $this->assertSame($original, $options);
    }
}
