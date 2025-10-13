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

namespace Playwright\Tests\Functional\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Input\Mouse;
use Playwright\Locator\Locator;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Locator::class)]
#[CoversClass(Mouse::class)]
final class MouseTest extends FunctionalTestCase
{
    public function testCanClickElement(): void
    {
        $this->goto('/mouse.html');

        $this->page->locator('#click-area')->click();

        $result = $this->page->locator('#click-result')->textContent();
        self::assertStringContainsString('Clicked at', $result);
    }

    public function testCanDoubleClickElement(): void
    {
        $this->goto('/mouse.html');

        $this->page->locator('#click-area')->dblclick();

        $result = $this->page->locator('#click-result')->textContent();
        self::assertSame('Double clicked!', $result);
    }

    public function testCanHoverOverElement(): void
    {
        $this->goto('/mouse.html');

        $this->page->locator('#hover-area')->hover();

        $result = $this->page->locator('#hover-result')->textContent();
        self::assertSame('Mouse entered', $result);
    }

    public function testCanTriggerContextMenu(): void
    {
        $this->goto('/mouse.html');

        $this->page->locator('#context-menu-area')->click(['button' => 'right']);

        $result = $this->page->locator('#context-result')->textContent();
        self::assertSame('Context menu triggered', $result);
    }

    public function testCanDragAndDrop(): void
    {
        $this->goto('/mouse.html');

        $source = $this->page->locator('#drag-source');
        $target = $this->page->locator('#drag-target');

        $source->dragTo($target);

        $result = $this->page->locator('#drag-result')->textContent();
        self::assertSame('Dropped!', $result);
    }
}
