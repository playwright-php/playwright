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
use Playwright\Page\Options\WaitForNavigationOptions;

#[CoversClass(WaitForNavigationOptions::class)]
final class WaitForNavigationOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new WaitForNavigationOptions(timeout: 5000.0);

        $this->assertSame($options, WaitForNavigationOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = WaitForNavigationOptions::from([
            'timeout' => 5000.0,
            'url' => '**/complete.html',
            'waitUntil' => 'commit',
        ]);

        $this->assertSame(5000.0, $options->timeout);
        $this->assertSame('**/complete.html', $options->url);
        $this->assertSame('commit', $options->waitUntil);
    }

    public function testItReturnsOnlySpecifiedOptions(): void
    {
        $options = new WaitForNavigationOptions(url: '**/complete.html', waitUntil: 'load');

        $this->assertSame([
            'url' => '**/complete.html',
            'waitUntil' => 'load',
        ], $options->toArray());
    }

    public function testItCarriesTheTimeout(): void
    {
        $options = new WaitForNavigationOptions(timeout: 2500);

        $this->assertSame(['timeout' => 2500.0], $options->toArray());
    }
}
