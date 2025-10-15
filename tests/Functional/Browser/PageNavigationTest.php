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
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
final class PageNavigationTest extends FunctionalTestCase
{
    public function testCanNavigateToPage(): void
    {
        $this->goto('/index.html');

        $this->assertUrlContains('index.html');
        $this->assertElementExists('#heading');
        $this->assertElementHasText('#heading', 'Test Index');
    }

    public function testCanNavigateBetweenPages(): void
    {
        $this->goto('/navigation.html');
        $this->assertUrlContains('navigation.html');

        $this->page->click('#link-page2');
        $this->assertUrlContains('page-2.html');
        $this->assertElementHasText('#heading', 'Page 2');

        $this->page->click('a[href="/page-3.html"]');
        $this->assertUrlContains('page-3.html');
        $this->assertElementHasText('#heading', 'Page 3');
    }

    public function testCanGoBackAndForward(): void
    {
        $this->goto('/navigation.html');
        $this->page->click('#link-page2');
        $this->assertUrlContains('page-2.html');

        $this->page->goBack();
        $this->assertUrlContains('navigation.html');

        $this->page->goForward();
        $this->assertUrlContains('page-2.html');
    }

    public function testCanReloadPage(): void
    {
        $this->goto('/index.html');
        $this->assertElementExists('#heading');

        $this->page->reload();
        $this->assertElementExists('#heading');
        $this->assertUrlContains('index.html');
    }

    public function testCanGetPageTitle(): void
    {
        $this->goto('/index.html');

        $title = $this->page->title();

        $this->assertSame('Index - Playwright PHP Tests', $title);
    }

    public function testCanNavigateToPageWithHash(): void
    {
        $this->goto('/navigation.html#section-a');

        $this->assertUrlContains('#section-a');
    }
}
