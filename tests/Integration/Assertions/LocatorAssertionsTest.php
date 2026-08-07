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
                <button id="aria-label-name" aria-label="Close dialog">X</button>
                <span id="labelled-by-source">Given name</span>
                <input id="labelled-by-name" aria-labelledby="labelled-by-source">
                <label for="labelled-name">Family name</label>
                <input id="labelled-name">
                <button id="text-name">  Save   changes  </button>
                <span id="described-by-source">Use your work address</span>
                <input id="described-by" aria-describedby="described-by-source">
                <input id="described-attribute" aria-description="Inline description">
                <input id="described-title" title="Tooltip description">
                <span id="error-source">Value is required</span>
                <input id="flagged-invalid" aria-invalid="true" aria-errormessage="error-source">
                <input id="constraint-invalid" required aria-errormessage="error-source">
                <input id="not-invalid" aria-errormessage="error-source">
                <ul id="snapshot"><li>One</li><li>Two</li></ul>
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
        $this->expect($attached)->toBeAttached();
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

    #[Test]
    public function itResolvesTheAccessibleNameFromEachSourceInOrder(): void
    {
        Expect::locator($this->page->locator('#aria-label-name'))->toHaveAccessibleName('Close dialog');
        Expect::locator($this->page->locator('#labelled-by-name'))->toHaveAccessibleName('Given name');
        Expect::locator($this->page->locator('#labelled-name'))->toHaveAccessibleName('Family name');
        Expect::locator($this->page->locator('#text-name'))->toHaveAccessibleName('Save changes');

        $this->assertTrue(true);
    }

    #[Test]
    public function itComparesTheAccessibleNameCaseInsensitivelyOnDemand(): void
    {
        Expect::locator($this->page->locator('#text-name'))
            ->toHaveAccessibleName('SAVE CHANGES', new AssertionOptions(ignoreCase: true));
        Expect::locator($this->page->locator('#text-name'))
            ->not()->toHaveAccessibleName('SAVE CHANGES', new AssertionOptions(timeoutMs: 0));

        $this->assertTrue(true);
    }

    #[Test]
    public function itResolvesTheAccessibleDescriptionFromEachSourceInOrder(): void
    {
        Expect::locator($this->page->locator('#described-attribute'))->toHaveAccessibleDescription('Inline description');
        Expect::locator($this->page->locator('#described-by'))->toHaveAccessibleDescription('Use your work address');
        Expect::locator($this->page->locator('#described-title'))->toHaveAccessibleDescription('Tooltip description');
        Expect::locator($this->page->locator('#aria-label-name'))->toHaveAccessibleDescription('');

        $this->assertTrue(true);
    }

    #[Test]
    public function itReadsTheAccessibleErrorMessageOnlyFromAnInvalidElement(): void
    {
        Expect::locator($this->page->locator('#flagged-invalid'))->toHaveAccessibleErrorMessage('Value is required');
        Expect::locator($this->page->locator('#constraint-invalid'))->toHaveAccessibleErrorMessage('Value is required');
        Expect::locator($this->page->locator('#not-invalid'))->toHaveAccessibleErrorMessage('');
        Expect::locator($this->page->locator('#not-invalid'))
            ->not()->toHaveAccessibleErrorMessage('Value is required', new AssertionOptions(timeoutMs: 0));

        $this->assertTrue(true);
    }

    #[Test]
    public function itMatchesTheAriaSnapshotOfASubtree(): void
    {
        Expect::locator($this->page->locator('#snapshot'))->toMatchAriaSnapshot(<<<'YAML'
            - list:
              - listitem: One
              - listitem: Two
            YAML);

        Expect::locator($this->page->locator('#snapshot'))->not()->toMatchAriaSnapshot(<<<'YAML'
            - list:
              - listitem: One
            YAML, new AssertionOptions(timeoutMs: 0));

        $this->assertTrue(true);
    }
}
