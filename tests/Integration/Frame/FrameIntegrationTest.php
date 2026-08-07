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
                <title>Inner frame</title>
                <h4>Inner</h4>
                <button id="btn" onclick="this.textContent='Clicked'">Click</button>
                <input type="text" placeholder="Frame Input" />
                <img src="/logo.png" alt="Frame Logo" />
                <div title="Frame Tooltip">Hover me</div>
                <span data-testid="frame-span">Test</span>
                <p>Frame Text</p>
            HTML,
            '/replacement.html' => '<title>Replacement frame</title><h1 id="replacement">Replacement frame</h1>',
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
    public function itEvaluatesExpressionsInFrameWithArguments(): void
    {
        $frame = $this->page->frame(['urlRegex' => '/inner\.html$/']);
        $this->assertNotNull($frame);

        $this->assertSame('Inner!', $frame->evaluate('(arg) => document.querySelector("h4").textContent + arg.suffix', ['suffix' => '!']));
        $this->assertSame('Inner', $frame->evaluate('return document.querySelector("h4").textContent;'));
        $this->assertSame('Inner frame', $frame->evaluate('document.title'));
    }

    #[Test]
    public function itReturnsTheFrameTitle(): void
    {
        $frame = $this->page->frame(['urlRegex' => '/inner\.html$/']);
        $this->assertNotNull($frame);

        $this->assertSame('Inner frame', $frame->title());
    }

    #[Test]
    public function itGetsAndSetsNativeFrameContent(): void
    {
        $frame = $this->innerFrame();

        $this->assertStringContainsString('<h4>Inner</h4>', $frame->content());
        $this->assertSame($frame, $frame->setContent('<title>Updated frame</title><h1 id="updated">Updated</h1>', ['waitUntil' => 'load']));
        $this->assertSame('Updated frame', $frame->title());
        $this->assertSame('Updated', $frame->locator('#updated')->textContent());
    }

    #[Test]
    public function itNavigatesANativeFrameAndReturnsItsResponse(): void
    {
        $frame = $this->innerFrame();

        $response = $frame->goto($this->routeUrl('/replacement.html'), ['waitUntil' => 'commit']);

        $this->assertNotNull($response);
        $this->assertSame(200, $response->status());
        $this->assertSame('Replacement frame', $frame->title());
    }

    #[Test]
    public function itWaitsForANativeFrameFunction(): void
    {
        $frame = $this->innerFrame();
        $frame->setContent('<div id="status">loading</div><script>setTimeout(() => document.querySelector("#status").textContent = "ready", 50)</script>');

        $this->assertSame($frame, $frame->waitForFunction(
            '() => document.querySelector("#status").textContent === "ready"',
            null,
            ['timeout' => 1000, 'polling' => 20],
        ));
    }

    #[Test]
    public function itWaitsForANativeFrameUrl(): void
    {
        $frame = $this->innerFrame();
        $target = $this->routeUrl('/replacement.html');
        $frame->evaluate('(target) => setTimeout(() => window.location.href = target, 50)', $target);

        $this->assertSame($frame, $frame->waitForURL($target, ['timeout' => 1000, 'waitUntil' => 'commit']));
        $this->assertSame('Replacement frame', $frame->title());
    }

    #[Test]
    public function itWaitsForANativeFrameNavigation(): void
    {
        $frame = $this->innerFrame();
        $target = $this->routeUrl('/replacement.html');
        $frame->evaluate('(target) => setTimeout(() => window.location.href = target, 50)', $target);

        $response = $frame->waitForNavigation([
            'url' => $target,
            'timeout' => 1000,
            'waitUntil' => 'commit',
        ]);

        $this->assertNotNull($response);
        $this->assertSame(200, $response->status());
    }

    #[Test]
    public function itDragsAndDropsInsideANativeFrame(): void
    {
        $frame = $this->innerFrame();
        $frame->setContent(<<<'HTML'
            <div id="source" draggable="true">Source</div>
            <div id="target">Target</div>
            <script>
                const target = document.querySelector('#target');
                target.addEventListener('dragover', event => event.preventDefault());
                target.addEventListener('drop', () => target.dataset.dropped = 'true');
            </script>
            HTML);

        $this->assertSame($frame, $frame->dragAndDrop('#source', '#target'));
        $this->assertSame('true', $frame->locator('#target')->getAttribute('data-dropped'));
    }

    #[Test]
    public function itAddsNativeFrameScriptAndStyleTags(): void
    {
        $frame = $this->innerFrame();
        $frame->setContent('<p id="styled">Styled</p>');

        $this->assertSame($frame, $frame->addScriptTag(['content' => 'window.frameScriptReady = true;']));
        $this->assertSame($frame, $frame->addStyleTag(['content' => '#styled { color: rgb(1, 2, 3); }']));

        $this->assertTrue($frame->evaluate('window.frameScriptReady'));
        $this->assertSame('rgb(1, 2, 3)', $frame->evaluate('getComputedStyle(document.querySelector("#styled")).color'));
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
    public function itExposesThePageOwningTheFrame(): void
    {
        $this->assertSame($this->page, $this->innerFrame()->page());
        $this->assertSame($this->page, $this->page->mainFrame()->page());
    }

    private function innerFrame(): Frame
    {
        $frame = $this->page->frame(['urlRegex' => '/inner\\.html$/']);
        $this->assertInstanceOf(Frame::class, $frame);

        return $frame;
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
