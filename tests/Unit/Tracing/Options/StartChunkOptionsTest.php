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
use Playwright\Tracing\Options\StartChunkOptions;

#[CoversClass(StartChunkOptions::class)]
final class StartChunkOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new StartChunkOptions(
            name: 'chunk-name',
            title: 'Chunk Title',
        );

        $this->assertSame('chunk-name', $options->name);
        $this->assertSame('Chunk Title', $options->title);

        $this->assertEquals([
            'name' => 'chunk-name',
            'title' => 'Chunk Title',
        ], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = StartChunkOptions::from([
            'name' => 'chunk-name',
            'title' => 'Chunk Title',
        ]);

        $this->assertSame('chunk-name', $options->name);
        $this->assertSame('Chunk Title', $options->title);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new StartChunkOptions(name: 'chunk-name');
        $options = StartChunkOptions::from($original);

        $this->assertSame($original, $options);
    }
}
