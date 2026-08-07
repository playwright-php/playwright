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
use Playwright\Page\Options\WaitForRequestOptions;

#[CoversClass(WaitForRequestOptions::class)]
final class WaitForRequestOptionsTest extends TestCase
{
    public function testCanBeCreatedFromConstructor(): void
    {
        $options = new WaitForRequestOptions(timeout: 1000.0);

        $this->assertSame(1000.0, $options->timeout);
        $this->assertSame(['timeout' => 1000.0], $options->toArray());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $options = WaitForRequestOptions::from(['timeout' => 250.0]);

        $this->assertSame(250.0, $options->timeout);
    }

    public function testCanBeCreatedFromSelf(): void
    {
        $original = new WaitForRequestOptions(timeout: 1000.0);

        $this->assertSame($original, WaitForRequestOptions::from($original));
    }

    public function testItOmitsAnUnsetTimeout(): void
    {
        $this->assertSame([], (new WaitForRequestOptions())->toArray());
    }
}
