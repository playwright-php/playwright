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

namespace Playwright\Tests\Functional\Interactions;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Locator\Locator;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Locator::class)]
final class ClickInteractionTest extends FunctionalTestCase
{
    public function testCanClickButton(): void
    {
        $this->goto('/events.html');

        $this->page->click('#click-btn');

        $result = $this->page->locator('#click-result')->textContent();
        self::assertSame('Clicked!', $result);
    }

    public function testCanDoubleClick(): void
    {
        $this->goto('/events.html');

        $this->page->locator('#dblclick-btn')->dblclick();

        $result = $this->page->locator('#click-result')->textContent();
        self::assertSame('Double Clicked!', $result);
    }

    public function testCanTypeInInput(): void
    {
        $this->goto('/events.html');

        $this->page->locator('#type-input')->fill('Hello World');

        $result = $this->page->locator('#input-result')->textContent();
        self::assertSame('Input: Hello World', $result);
    }

    public function testCanHoverOverElement(): void
    {
        $this->goto('/events.html');

        $this->page->locator('#hover-target')->hover();

        $result = $this->page->locator('#hover-result')->textContent();
        self::assertSame('Hovered!', $result);
    }
}
