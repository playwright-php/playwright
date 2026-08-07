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
use Playwright\Locator\Options\SetCheckedOptions;

#[CoversClass(SetCheckedOptions::class)]
final class SetCheckedOptionsTest extends TestCase
{
    public function testItBuildsFromAnArray(): void
    {
        $options = SetCheckedOptions::from(['force' => true, 'timeout' => 500.0]);

        $this->assertSame(['force' => true, 'timeout' => 500.0], $options->toArray());
    }

    public function testItOmitsNullOptions(): void
    {
        $this->assertSame([], (new SetCheckedOptions())->toArray());
    }

    public function testItReturnsAnInstanceUnchanged(): void
    {
        $options = new SetCheckedOptions(force: true);

        $this->assertSame($options, SetCheckedOptions::from($options));
    }
}
