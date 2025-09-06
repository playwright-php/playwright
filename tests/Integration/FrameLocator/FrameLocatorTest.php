<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\FrameLocator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Frame\FrameLocator;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use PlaywrightPHP\Tests\Support\RouteServerTestTrait;

#[CoversClass(FrameLocator::class)]
class FrameLocatorTest extends TestCase
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
                <h1>Frame Locator Test</h1>
                <iframe src="/frame.html" id="frame1"></iframe>
            HTML,
            '/frame.html' => <<<'HTML'
                <h2>Frame Content</h2>
                <button>Click Me</button>
            HTML,
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itLocatesAnElementWithinAFrame(): void
    {
        $frameLocator = $this->page->frameLocator('#frame1');
        $button = $frameLocator->locator('button');

        $this->assertEquals('Click Me', $button->textContent());
    }

    #[Test]
    public function itClicksAnElementWithinAFrame(): void
    {
        $frameLocator = $this->page->frameLocator('#frame1');
        $button = $frameLocator->locator('button');

        $button->click();

        $this->expectNotToPerformAssertions();
    }

    private static function findFreePort(): int
    {
        return 0;
    }
}
