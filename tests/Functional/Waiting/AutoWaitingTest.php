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

namespace Playwright\Tests\Functional\Waiting;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Locator\Locator;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Locator::class)]
final class AutoWaitingTest extends FunctionalTestCase
{
    public function testWaitsForElementToAppear(): void
    {
        $this->goto('/waiting.html');

        $this->page->click('#show-after-delay');

        $this->page->waitForSelector('#delayed-element.visible');

        $text = $this->page->locator('#delayed-element')->textContent();
        self::assertSame('This appeared after a delay', $text);
    }

    public function testWaitsForLoadingToComplete(): void
    {
        $this->goto('/waiting.html');

        $this->page->click('#trigger-loading');

        $this->page->waitForSelector('#loaded-content:not(.hidden)');

        $content = $this->page->locator('#loaded-content')->textContent();
        self::assertSame('Content loaded!', $content);
    }

    public function testWaitsForDynamicElementsToBeAdded(): void
    {
        $this->goto('/waiting.html');

        $this->page->click('#add-elements');

        $this->page->waitForSelector('.list-item:nth-child(3)');

        $items = $this->page->locator('.list-item')->all();
        self::assertCount(3, $items);
    }

    public function testWaitsForElementToBeEnabled(): void
    {
        $this->goto('/waiting.html');

        $button = $this->page->locator('#enable-button-later');
        self::assertFalse($button->isEnabled());

        $this->page->click('#trigger-enable');

        $this->page->waitForSelector('#enable-button-later:enabled');

        self::assertTrue($button->isEnabled());
    }

    public function testWaitsForElementToBeVisible(): void
    {
        $this->goto('/waiting.html');

        $element = $this->page->locator('#delayed-element');
        self::assertFalse($element->isVisible());

        $this->page->click('#show-after-delay');

        $element->waitFor(['state' => 'visible']);

        self::assertTrue($element->isVisible());
    }

    public function testWaitsForAjaxContentToLoad(): void
    {
        $this->goto('/waiting.html');

        $this->page->click('#fetch-data');

        $this->page->waitForSelector('#fetched-data');

        $data = $this->page->locator('#fetched-data')->textContent();
        self::assertSame('Data from server', $data);
    }

    public function testClickAutomaticallyWaitsForElement(): void
    {
        $this->goto('/waiting.html');

        $this->page->click('#show-after-delay');

        $this->page->waitForSelector('#delayed-element.visible');

        self::assertTrue($this->page->locator('#delayed-element')->isVisible());
    }

    public function testFillAutomaticallyWaitsForElement(): void
    {
        $this->goto('/waiting.html');

        $this->page->evaluate(<<<'JS'
setTimeout(() => {
    const input = document.createElement('input');
    input.id = 'delayed-input';
    input.type = 'text';
    document.body.appendChild(input);
}, 500);
JS);

        $this->page->waitForSelector('#delayed-input');

        $this->page->locator('#delayed-input')->fill('test value');

        $value = $this->page->locator('#delayed-input')->inputValue();
        self::assertSame('test value', $value);
    }

    public function testCanWaitForMultipleStates(): void
    {
        $this->goto('/waiting.html');

        $this->page->click('#show-after-delay');

        $element = $this->page->locator('#delayed-element');
        $element->waitFor(['state' => 'visible']);

        self::assertTrue($element->isVisible());

        $count = $element->count();
        self::assertSame(1, $count);
    }
}
