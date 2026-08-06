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

        self::assertTrue($attached->isAttached());
        self::assertTrue($editable->isEditable());

        Expect::locator($attached)->toBeAttached();
        Expect::locator($editable)->toBeEditable();
    }
}
