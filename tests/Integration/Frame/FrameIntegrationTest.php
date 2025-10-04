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

namespace Playwright\Tests\Integration\Frame;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Frame\Frame;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;

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
                <input type="text" placeholder="Frame Input" />
                <img src="/logo.png" alt="Frame Logo" />
                <div title="Frame Tooltip">Hover me</div>
                <span data-testid="frame-span">Test</span>
                <p>Frame Text</p>
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

    #[Test]
    public function itUsesGetByTextInFrame(): void
    {
        $frame = $this->page->frame(['urlRegex' => '/inner\.html$/']);
        $this->assertNotNull($frame);

        $locator = $frame->getByText('Frame Text');
        $this->assertSame('Frame Text', $locator->textContent());
    }

    #[Test]
    public function itUsesGetByPlaceholderInFrame(): void
    {
        $frame = $this->page->frame(['urlRegex' => '/inner\.html$/']);
        $this->assertNotNull($frame);

        $locator = $frame->getByPlaceholder('Frame Input');
        $placeholder = $locator->getAttribute('placeholder');
        $this->assertSame('Frame Input', $placeholder);
    }

    #[Test]
    public function itUsesGetByAltTextInFrame(): void
    {
        $frame = $this->page->frame(['urlRegex' => '/inner\.html$/']);
        $this->assertNotNull($frame);

        $locator = $frame->getByAltText('Frame Logo');
        $alt = $locator->getAttribute('alt');
        $this->assertSame('Frame Logo', $alt);
    }

    #[Test]
    public function itUsesGetByTitleInFrame(): void
    {
        $frame = $this->page->frame(['urlRegex' => '/inner\.html$/']);
        $this->assertNotNull($frame);

        $locator = $frame->getByTitle('Frame Tooltip');
        $title = $locator->getAttribute('title');
        $this->assertSame('Frame Tooltip', $title);
    }

    #[Test]
    public function itUsesGetByTestIdInFrame(): void
    {
        $frame = $this->page->frame(['urlRegex' => '/inner\.html$/']);
        $this->assertNotNull($frame);

        $locator = $frame->getByTestId('frame-span');
        $this->assertSame('Test', $locator->textContent());
    }

    #[Test]
    public function itUsesFrameLocatorGetByText(): void
    {
        $frameLocator = $this->page->frameLocator('iframe#outer >> iframe#middle >> iframe#inner');
        $locator = $frameLocator->getByText('Frame Text');
        $this->assertSame('Frame Text', $locator->textContent());
    }

    #[Test]
    public function itUsesFrameLocatorGetByPlaceholder(): void
    {
        $frameLocator = $this->page->frameLocator('iframe#outer >> iframe#middle >> iframe#inner');
        $locator = $frameLocator->getByPlaceholder('Frame Input');
        $locator->fill('test value');
        $this->assertSame('test value', $locator->inputValue());
    }
}
