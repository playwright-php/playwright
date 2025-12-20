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
use Playwright\Tracing\Options\StopOptions;

#[CoversClass(StopOptions::class)]
final class StopOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new StopOptions(path: 'trace.zip');

        $this->assertSame('trace.zip', $options->path);
        $this->assertEquals(['path' => 'trace.zip'], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = StopOptions::from(['path' => 'trace.zip']);

        $this->assertSame('trace.zip', $options->path);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new StopOptions(path: 'trace.zip');
        $options = StopOptions::from($original);

        $this->assertSame($original, $options);
    }
}
