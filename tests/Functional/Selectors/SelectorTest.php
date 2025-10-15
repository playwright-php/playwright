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

namespace Playwright\Tests\Functional\Selectors;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Locator\Locator;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Locator::class)]
final class SelectorTest extends FunctionalTestCase
{
    public function testCanSelectById(): void
    {
        $this->goto('/selectors.html');

        $element = $this->page->locator('#unique-element');

        self::assertSame(1, $element->count());
        self::assertSame('By ID', $element->textContent());
    }

    public function testCanSelectByClass(): void
    {
        $this->goto('/selectors.html');

        $elements = $this->page->locator('.test-class');

        self::assertSame(2, $elements->count());
    }

    public function testCanSelectByDataAttribute(): void
    {
        $this->goto('/selectors.html');

        $button = $this->page->locator('[data-testid="test-button"]');

        self::assertTrue($button->isVisible());
        self::assertSame('By Data Attribute', $button->textContent());
    }

    public function testCanSelectByText(): void
    {
        $this->goto('/selectors.html');

        $button = $this->page->getByText('Click Here');

        self::assertTrue($button->isVisible());
    }

    public function testCanCheckVisibility(): void
    {
        $this->goto('/selectors.html');

        $visibleElement = $this->page->locator('#visible-element');
        $hiddenElement = $this->page->locator('#hidden-element');

        self::assertTrue($visibleElement->isVisible());
        self::assertFalse($hiddenElement->isVisible());
    }

    public function testCanCheckEnabledState(): void
    {
        $this->goto('/selectors.html');

        $enabledButton = $this->page->locator('#enabled-button');
        $disabledButton = $this->page->locator('#disabled-button');

        self::assertTrue($enabledButton->isEnabled());
        self::assertFalse($disabledButton->isEnabled());
    }

    public function testCanCheckCheckedState(): void
    {
        $this->goto('/selectors.html');

        $checkedCheckbox = $this->page->locator('#checked-checkbox');
        $uncheckedCheckbox = $this->page->locator('#unchecked-checkbox');

        self::assertTrue($checkedCheckbox->isChecked());
        self::assertFalse($uncheckedCheckbox->isChecked());
    }
}
