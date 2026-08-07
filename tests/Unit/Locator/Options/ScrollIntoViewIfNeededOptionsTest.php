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
use Playwright\Locator\Options\ScrollIntoViewIfNeededOptions;

#[CoversClass(ScrollIntoViewIfNeededOptions::class)]
final class ScrollIntoViewIfNeededOptionsTest extends TestCase
{
    public function testItBuildsFromAnArray(): void
    {
        $options = ScrollIntoViewIfNeededOptions::from(['timeout' => 500.0]);

        $this->assertSame(['timeout' => 500.0], $options->toArray());
    }

    public function testItOmitsNullOptions(): void
    {
        $this->assertSame([], (new ScrollIntoViewIfNeededOptions())->toArray());
    }
}
