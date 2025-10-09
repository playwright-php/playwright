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

namespace Playwright\Tests\Integration\Locator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\Locator;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Locator::class)]
class LocatorFilterTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->installRouteServer($this->page, [
            '/index.html' => <<<'HTML'
                <div class="items">
                    <div class="item">First item</div>
                    <div class="item active">Second item</div>
                    <div class="item">Third item</div>
                </div>
                <iframe id="test-frame" src="/frame.html"></iframe>
            HTML,
            '/frame.html' => '<h1>Frame Content</h1>',
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itCombinesLocatorsWithAnd(): void
    {
        $items = $this->page->locator('.item');
        $active = $this->page->locator('.active');

        $combined = $items->and($active);
        $this->assertInstanceOf(Locator::class, $combined);
    }

    #[Test]
    public function itGetsContentFrame(): void
    {
        $iframe = $this->page->locator('#test-frame');
        $frameLocator = $iframe->contentFrame();

        $heading = $frameLocator->locator('h1');
        $this->assertSame('Frame Content', $heading->textContent());
    }
}
