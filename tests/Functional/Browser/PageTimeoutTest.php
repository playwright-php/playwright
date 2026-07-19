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

namespace Playwright\Tests\Functional\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Exception\TimeoutException;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
final class PageTimeoutTest extends FunctionalTestCase
{
    public function testSetDefaultTimeoutAppliesToWaits(): void
    {
        $this->goto('/index.html');

        $this->page->setDefaultTimeout(500);

        $start = microtime(true);

        try {
            $this->page->waitForSelector('#does-not-exist');
            self::fail('Expected a TimeoutException');
        } catch (TimeoutException) {
        }

        $elapsed = microtime(true) - $start;
        self::assertLessThan(10.0, $elapsed, 'The 500ms default timeout should apply instead of the 30s built-in default');
    }

    public function testSetDefaultNavigationTimeoutIsAccepted(): void
    {
        $this->goto('/index.html');

        $result = $this->page->setDefaultNavigationTimeout(10000);

        self::assertSame($this->page, $result);
    }
}
