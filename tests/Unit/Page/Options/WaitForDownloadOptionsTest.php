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
use Playwright\Page\Options\WaitForDownloadOptions;

#[CoversClass(WaitForDownloadOptions::class)]
final class WaitForDownloadOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new WaitForDownloadOptions(timeout: 5000.0);

        $this->assertSame($options, WaitForDownloadOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $this->assertSame(['timeout' => 5000.0], WaitForDownloadOptions::from(['timeout' => 5000])->toArray());
    }

    public function testItLeavesTheTimeoutOutWhenNotSet(): void
    {
        $this->assertSame([], WaitForDownloadOptions::from([])->toArray());
    }
}
