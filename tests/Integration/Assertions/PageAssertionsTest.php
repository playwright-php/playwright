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

namespace Playwright\Tests\Integration\Assertions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Assertions\AssertionOptions;
use Playwright\Assertions\Expect;
use Playwright\Assertions\PageAssertions;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(PageAssertions::class)]
final class PageAssertionsTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    protected function setUp(): void
    {
        $this->setUpPlaywright();
        $this->installRouteServer($this->page, [
            '/index.html' => <<<'HTML'
                <h1>Report</h1>
                <ul><li>One</li><li>Two</li></ul>
            HTML,
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    protected function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itMatchesTheAriaSnapshotOfTheBody(): void
    {
        Expect::page($this->page)->toMatchAriaSnapshot(<<<'YAML'
            - heading "Report" [level=1]
            - list:
              - listitem: One
              - listitem: Two
            YAML);

        $this->assertTrue(true);
    }

    #[Test]
    public function itRejectsAnAriaSnapshotThatDescribesADifferentBody(): void
    {
        Expect::page($this->page)->not()->toMatchAriaSnapshot(<<<'YAML'
            - heading "Report" [level=1]
            YAML, new AssertionOptions(timeoutMs: 0));

        $this->assertTrue(true);
    }
}
