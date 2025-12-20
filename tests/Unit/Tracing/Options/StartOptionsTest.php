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

namespace Playwright\Tests\Unit\Tracing\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Tracing\Options\StartOptions;

#[CoversClass(StartOptions::class)]
final class StartOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new StartOptions(
            name: 'trace-name',
            screenshots: true,
            snapshots: true,
            sources: false,
            title: 'Trace Title',
        );

        $this->assertSame('trace-name', $options->name);
        $this->assertTrue($options->screenshots);
        $this->assertTrue($options->snapshots);
        $this->assertFalse($options->sources);
        $this->assertSame('Trace Title', $options->title);

        $this->assertEquals([
            'name' => 'trace-name',
            'screenshots' => true,
            'snapshots' => true,
            'sources' => false,
            'title' => 'Trace Title',
        ], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = StartOptions::from([
            'name' => 'trace-name',
            'screenshots' => true,
            'snapshots' => true,
            'sources' => false,
            'title' => 'Trace Title',
        ]);

        $this->assertSame('trace-name', $options->name);
        $this->assertTrue($options->screenshots);
        $this->assertTrue($options->snapshots);
        $this->assertFalse($options->sources);
        $this->assertSame('Trace Title', $options->title);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new StartOptions(name: 'trace-name');
        $options = StartOptions::from($original);

        $this->assertSame($original, $options);
    }
}
