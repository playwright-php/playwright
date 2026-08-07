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
use Playwright\Locator\Options\DispatchEventOptions;

#[CoversClass(DispatchEventOptions::class)]
final class DispatchEventOptionsTest extends TestCase
{
    public function testItBuildsFromAnArray(): void
    {
        $options = DispatchEventOptions::from(['timeout' => 500.0]);

        $this->assertSame(['timeout' => 500.0], $options->toArray());
    }

    public function testItOmitsNullOptions(): void
    {
        $this->assertSame([], (new DispatchEventOptions())->toArray());
    }

    public function testItReturnsAnInstanceUnchanged(): void
    {
        $options = new DispatchEventOptions(timeout: 500.0);

        $this->assertSame($options, DispatchEventOptions::from($options));
    }
}
