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

namespace Playwright\Tests\Integration\Selector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Selector\Selectors;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

#[CoversClass(Selectors::class)]
class SelectorsTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->installRouteServer($this->page, [
            '/index.html' => <<<'HTML'
                <h1>Selectors Test</h1>
                <button id="test-button" data-testid="submit">Submit</button>
                <input id="test-input" data-custom-id="email-field" placeholder="Email">
                <div id="custom-selector">Custom</div>
            HTML,
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itUsesDefaultTestIdAttribute(): void
    {
        $locator = $this->page->getByTestId('submit');
        $this->assertSame('Submit', $locator->textContent());
    }

    #[Test]
    public function itGetsAndSetsTestIdAttribute(): void
    {
        $this->assertSame('data-testid', $this->playwright->selectors()->getTestIdAttribute());

        $this->playwright->selectors()->setTestIdAttribute('data-custom-id');
        $this->assertSame('data-custom-id', $this->playwright->selectors()->getTestIdAttribute());
    }

    #[Test]
    public function itCallsRegisterWithoutError(): void
    {
        $script = <<<'JS'
            {
                query(root, selector) {
                    return root.querySelector(selector);
                },
                queryAll(root, selector) {
                    return Array.from(root.querySelectorAll(selector));
                }
            }
        JS;

        $this->playwright->selectors()->register('custom', $script);
        $this->expectNotToPerformAssertions();
    }
}
