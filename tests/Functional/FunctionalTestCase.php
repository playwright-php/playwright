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

namespace Playwright\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Playwright\Testing\PlaywrightTestCaseTrait;

/**
 * Base class for functional tests using real Playwright browsers.
 *
 * Provides browser lifecycle management and common utilities.
 */
abstract class FunctionalTestCase extends TestCase
{
    use PlaywrightTestCaseTrait;

    /**
     * Start Playwright and launch browser once for all tests in the class.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    /**
     * Create new browser context and page for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpPlaywright();
    }

    /**
     * Close context after each test.
     */
    protected function tearDown(): void
    {
        $this->tearDownPlaywright();

        parent::tearDown();
    }

    /**
     * Close browser after all tests in the class.
     */
    public static function tearDownAfterClass(): void
    {
        self::closeSharedPlaywright();

        parent::tearDownAfterClass();
    }

    /**
     * Get the base URL for test fixtures.
     *
     * Override this method if you need a different test server URL.
     */
    protected function getBaseUrl(): string
    {
        return 'http://localhost:8888';
    }

    /**
     * Navigate to a fixture page.
     */
    protected function goto(string $path): void
    {
        $url = $this->getBaseUrl().$path;
        $this->page->goto($url);
    }

    /**
     * Assert that the current URL contains the given string.
     */
    protected function assertUrlContains(string $needle): void
    {
        $currentUrl = $this->page->url();
        self::assertStringContainsString(
            $needle,
            $currentUrl,
            \sprintf('Expected URL to contain "%s", got "%s"', $needle, $currentUrl)
        );
    }

    /**
     * Assert that the current URL equals the given URL.
     */
    protected function assertUrlEquals(string $expected): void
    {
        $currentUrl = $this->page->url();
        self::assertSame(
            $expected,
            $currentUrl,
            \sprintf('Expected URL to be "%s", got "%s"', $expected, $currentUrl)
        );
    }

    /**
     * Assert that an element with the given selector exists.
     */
    protected function assertElementExists(string $selector): void
    {
        $locator = $this->page->locator($selector);
        $count = $locator->count();

        self::assertGreaterThan(
            0,
            $count,
            \sprintf('Expected element matching selector "%s" to exist', $selector)
        );
    }

    /**
     * Assert that an element with the given selector is visible.
     */
    protected function assertElementVisible(string $selector): void
    {
        $locator = $this->page->locator($selector);
        $isVisible = $locator->isVisible();

        self::assertTrue(
            $isVisible,
            \sprintf('Expected element matching selector "%s" to be visible', $selector)
        );
    }

    /**
     * Assert that an element with the given selector has the expected text.
     */
    protected function assertElementHasText(string $selector, string $expectedText): void
    {
        $locator = $this->page->locator($selector);
        $actualText = $locator->textContent();

        self::assertSame(
            $expectedText,
            $actualText,
            \sprintf('Expected element "%s" to have text "%s", got "%s"', $selector, $expectedText, $actualText)
        );
    }
}
