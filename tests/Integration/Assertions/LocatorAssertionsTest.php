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
use Playwright\Assertions\LocatorAssertions;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(LocatorAssertions::class)]
final class LocatorAssertionsTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    protected function setUp(): void
    {
        $this->setUpPlaywright();
        $this->installRouteServer($this->page, [
            '/index.html' => <<<'HTML'
                <div id="attached">Attached</div>
                <input id="editable" type="text" value="Editable">
                <div id="viewport" style="width: 100px; height: 100px">In viewport</div>
                <div id="property"></div>
                <select id="values" multiple>
                    <option value="first" selected>First</option>
                    <option value="second" selected>Second</option>
                </select>
                <button id="native-role" class="primary active">Native role</button>
                <div id="explicit-role" role="tab">Explicit role</div>
                <div id="off-screen" style="position: absolute; left: -9999px; width: 100px; height: 100px">Off screen</div>
                <script>document.querySelector('#property').state = {ready: true, values: [1, 2]};</script>
            HTML,
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    protected function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itAssertsAttachedAndEditableLocators(): void
    {
        $attached = $this->page->locator('#attached');
        $editable = $this->page->locator('#editable');

        $this->assertTrue($attached->isAttached());
        $this->assertTrue($editable->isEditable());

        Expect::locator($attached)->toBeAttached();
        Expect::locator($editable)->toBeEditable();
    }

    #[Test]
    public function itAssertsDomBasedLocatorState(): void
    {
        Expect::locator($this->page->locator('#viewport'))->toBeInViewport(new AssertionOptions(ratio: 1.0));
        Expect::locator($this->page->locator('#property'))->toHaveJSProperty('state', ['ready' => true, 'values' => [1, 2]]);
        Expect::locator($this->page->locator('#values'))->toHaveValues(['first', 'second']);
        Expect::locator($this->page->locator('#native-role'))->toHaveRole('button');
        Expect::locator($this->page->locator('#explicit-role'))->toHaveRole('tab');
        Expect::locator($this->page->locator('#native-role'))->toContainClass('primary active');
        Expect::locator($this->page->locator('#native-role'))->not()->toHaveRole('link', new AssertionOptions(timeoutMs: 0));

        $this->assertTrue(true);
    }

    #[Test]
    public function itRejectsAnElementOutsideTheViewportWithTheDefaultRatio(): void
    {
        Expect::locator($this->page->locator('#viewport'))->toBeInViewport();
        Expect::locator($this->page->locator('#off-screen'))
            ->not()->toBeInViewport(new AssertionOptions(timeoutMs: 0));

        $this->assertTrue(true);
    }
}
