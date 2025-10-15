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

namespace Playwright\Tests\Integration\Functional;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Playwright\Locator\Locator;
use Playwright\Page\Page;
use Playwright\Tests\Support\FunctionalTestCase;

/**
 * Example functional test demonstrating the automatic fixture server.
 *
 * The fixture server is automatically started before tests run and stopped
 * after tests complete. Tests can navigate to fixture URLs using the
 * fixtureUrl() helper method.
 */
#[CoversClass(Locator::class)]
#[CoversClass(Page::class)]
final class BasicFunctionalTest extends FunctionalTestCase
{
    #[Test]
    public function itCanLoadIndexPage(): void
    {
        $this->page->goto($this->fixtureUrl('/index.html'));

        $heading = $this->page->locator('#heading')->textContent();
        $this->assertSame('Test Index', $heading);

        $content = $this->page->locator('#content')->textContent();
        $this->assertStringContainsString('Minimal test fixture', $content);
    }

    #[Test]
    public function itCanNavigateToFormsPage(): void
    {
        $this->page->goto($this->fixtureUrl('/index.html'));

        $this->page->click('a[href="/forms.html"]');
        $this->page->waitForURL('**/forms.html');

        $this->assertStringContainsString('/forms.html', $this->page->url());
    }

    #[Test]
    public function itReturns404ForNonExistentPage(): void
    {
        $response = $this->page->goto($this->fixtureUrl('/non-existent.html'));

        $this->assertSame(404, $response->status());
        $this->assertStringContainsString('404 Not Found', $this->page->content());
    }

    #[Test]
    public function itDefaultsToIndexHtmlForRoot(): void
    {
        $this->page->goto($this->fixtureUrl('/'));

        $heading = $this->page->locator('#heading')->textContent();
        $this->assertSame('Test Index', $heading);
    }
}
