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
use Playwright\Page\Options\DragAndDropOptions;

#[CoversClass(DragAndDropOptions::class)]
final class DragAndDropOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new DragAndDropOptions(force: true);

        $this->assertSame($options, DragAndDropOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = DragAndDropOptions::from(['strict' => true, 'timeout' => 500.0]);

        $this->assertTrue($options->strict);
        $this->assertSame(500.0, $options->timeout);
    }

    public function testItReturnsOnlyProvidedOptions(): void
    {
        $options = new DragAndDropOptions(sourcePosition: ['x' => 1.0, 'y' => 2.0], trial: true);

        $this->assertSame([
            'sourcePosition' => ['x' => 1.0, 'y' => 2.0],
            'trial' => true,
        ], $options->toArray());
    }
}
