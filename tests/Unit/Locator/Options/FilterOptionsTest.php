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
use Playwright\Locator\LocatorInterface;
use Playwright\Locator\Options\FilterOptions;

#[CoversClass(FilterOptions::class)]
final class FilterOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $has = $this->createMock(LocatorInterface::class);
        $hasNot = $this->createMock(LocatorInterface::class);

        $options = new FilterOptions(
            has: $has,
            hasNot: $hasNot,
            hasText: 'foo',
            hasNotText: 'bar',
        );

        $this->assertSame($has, $options->has);
        $this->assertSame($hasNot, $options->hasNot);
        $this->assertEquals('foo', $options->hasText);
        $this->assertEquals('bar', $options->hasNotText);

        $this->assertEquals([
            'has' => $has,
            'hasNot' => $hasNot,
            'hasText' => 'foo',
            'hasNotText' => 'bar',
        ], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $has = $this->createMock(LocatorInterface::class);
        $hasNot = $this->createMock(LocatorInterface::class);

        $options = FilterOptions::from([
            'has' => $has,
            'hasNot' => $hasNot,
            'hasText' => 'foo',
            'hasNotText' => 'bar',
        ]);

        $this->assertSame($has, $options->has);
        $this->assertSame($hasNot, $options->hasNot);
        $this->assertEquals('foo', $options->hasText);
        $this->assertEquals('bar', $options->hasNotText);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new FilterOptions(hasText: 'foo');
        $options = FilterOptions::from($original);

        $this->assertSame($original, $options);
    }
}
