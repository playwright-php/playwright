<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Frame;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Frame\Frame;
use PlaywrightPHP\Testing\PlaywrightTestCaseTrait;
use PlaywrightPHP\Tests\Support\RouteServerTestTrait;

#[CoversClass(Frame::class)]
class FrameIntegrationTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->installRouteServer($this->page, [
            '/index.html' => <<<'HTML'
                <h1>Frames</h1>
                <iframe id="outer" src="/outer.html"></iframe>
            HTML,
            '/outer.html' => <<<'HTML'
                <h2>Outer</h2>
                <iframe id="middle" src="/middle.html"></iframe>
            HTML,
            '/middle.html' => <<<'HTML'
                <h3>Middle</h3>
                <iframe id="inner" name="inn" src="/inner.html"></iframe>
            HTML,
            '/inner.html' => <<<'HTML'
                <h4>Inner</h4>
                <button id="btn" onclick="this.textContent='Clicked'">Click</button>
            HTML,
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itEnumeratesNestedFrames(): void
    {
        $frames = $this->page->frames();
        $this->assertNotEmpty($frames);

        $selectors = array_map(static fn ($f) => (string) $f, $frames);
        $this->assertTrue(
            (bool) array_filter($selectors, fn ($s) => str_contains($s, 'iframe#outer >> iframe#middle >> iframe#inner')),
            'Should include nested inner frame selector'
        );
    }

    #[Test]
    public function itFindsFrameByUrlAndInteracts(): void
    {
        $frame = $this->page->frame(['urlRegex' => '/inner\.html$/']);
        $this->assertNotNull($frame);

        $frame->waitForLoadState('domcontentloaded');
        $button = $frame->locator('#btn');
        $button->click();
        $this->assertSame('Clicked', $button->textContent());
    }
}
