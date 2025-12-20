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

namespace Playwright\Tests\Integration\Testing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Testing\Expect;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Expect::class)]
class ExpectTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    public static function setUpBeforeClass(): void
    {
    }

    public static function tearDownAfterClass(): void
    {
    }

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->installRouteServer($this->page, [
            '/index.html' => <<<'HTML'
                <title>Expect Test</title>
                <h1>Expect Test</h1>
                <input type="text" id="input-text" value="initial value">
                <input type="checkbox" id="input-checkbox" checked>
                <button id="button-1">Button 1</button>
                <button id="button-2" disabled>Button 2</button>
                <div id="div-1" style="width: 50px; height: 50px; background-color: blue; color: red;">Visible Div</div>
                <div id="div-2" style="display: none;">Hidden Div</div>
            HTML,
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itAssertsVisibility(): void
    {
        $expect = $this->expect($this->page->locator('#div-1'));
        $expect->toBeVisible();

        $expect = $this->expect($this->page->locator('#div-2'));
        $expect->not()->toBeVisible();

        $expect = $this->expect($this->page->locator('#div-2'));
        $expect->toBeHidden();
    }

    #[Test]
    public function itAssertsTextContent(): void
    {
        $expect = $this->expect($this->page->locator('h1'));
        $expect->toHaveText('Expect Test');

        $expect = $this->expect($this->page->locator('h1'));
        $expect->not()->toHaveText('Wrong Text');
    }

    #[Test]
    public function itAssertsContainAndExactText(): void
    {
        $h1 = $this->page->locator('h1');

        $this->expect($h1)->toContainText('Expect');
        $this->expect($h1)->not()->toContainText('Wrong');

        $this->expect($h1)->toHaveExactText('Expect Test');
        $this->expect($h1)->not()->toHaveExactText('Expect');
    }

    #[Test]
    public function itAssertsInputValue(): void
    {
        $expect = $this->expect($this->page->locator('#input-text'));
        $expect->toHaveValue('initial value');

        $expect = $this->expect($this->page->locator('#input-text'));
        $expect->not()->toHaveValue('wrong value');
    }

    #[Test]
    public function itAssertsAttributeValue(): void
    {
        $expect = $this->expect($this->page->locator('#input-text'));
        $expect->toHaveAttribute('type', 'text');

        $expect = $this->expect($this->page->locator('#input-text'));
        $expect->not()->toHaveAttribute('type', 'password');
    }

    #[Test]
    public function itAssertsCheckedState(): void
    {
        $expect = $this->expect($this->page->locator('#input-checkbox'));
        $expect->toBeChecked();

        $this->page->locator('#input-checkbox')->uncheck();

        $expect = $this->expect($this->page->locator('#input-checkbox'));
        $expect->not()->toBeChecked();
    }

    #[Test]
    public function itAssertsEnabledState(): void
    {
        $expect = $this->expect($this->page->locator('#button-1'));
        $expect->toBeEnabled();

        $expect = $this->expect($this->page->locator('#button-2'));
        $expect->not()->toBeEnabled();

        $expect = $this->expect($this->page->locator('#button-2'));
        $expect->toBeDisabled();
    }

    #[Test]
    public function itAssertsElementCount(): void
    {
        $expect = $this->expect($this->page->locator('button'));
        $expect->toHaveCount(2);

        $expect = $this->expect($this->page->locator('button'));
        $expect->not()->toHaveCount(1);
    }

    #[Test]
    public function itAssertsCssProperty(): void
    {
        $expect = $this->expect($this->page->locator('#div-1'));
        $expect->toHaveCSS('width', '50px');

        $expect = $this->expect($this->page->locator('#div-1'));
        $expect->not()->toHaveCSS('width', '10px');
    }

    #[Test]
    public function itAssertsPageTitleAndUrl(): void
    {
        $expectPage = $this->expect($this->page);
        $expectPage->toHaveTitle('Expect Test');

        $expectedUrl = $this->routeUrl('/index.html');
        $expectUrl = $this->expect($this->page);
        $expectUrl->toHaveURL($expectedUrl);

        $expectNotTitle = $this->expect($this->page);
        $expectNotTitle->not()->toHaveTitle('Wrong Title');

        $expectNotUrl = $this->expect($this->page);
        $expectNotUrl->not()->toHaveURL($expectedUrl.'?q=1');
    }

    private static function findFreePort(): int
    {
        return 0;
    }
}
