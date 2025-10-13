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

namespace Playwright\Tests\Functional\Frame;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Frame\FrameLocator;
use Playwright\Frame\FrameLocatorInterface;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(FrameLocator::class)]
final class FrameTest extends FunctionalTestCase
{
    public function testCanAccessFrameLocator(): void
    {
        $this->goto('/frames.html');

        $this->page->waitForSelector('#frame1');

        $frame = $this->page->frameLocator('#frame1');

        self::assertInstanceOf(FrameLocatorInterface::class, $frame);
    }

    public function testCanInteractWithFrameContent(): void
    {
        $this->goto('/frames.html');

        $this->page->waitForSelector('#frame1');

        $frame = $this->page->frameLocator('#frame1');

        $heading = $frame->locator('#frame-heading')->textContent();
        self::assertSame('This is frame content', $heading);
    }

    public function testCanClickButtonInFrame(): void
    {
        $this->goto('/frames.html');

        $this->page->waitForSelector('#frame1');

        $frame = $this->page->frameLocator('#frame1');
        $frame->locator('#frame-button')->click();

        $text = $frame->locator('#frame-text')->textContent();
        self::assertSame('Button clicked in frame', $text);
    }

    public function testCanFillInputInFrame(): void
    {
        $this->goto('/frames.html');

        $this->page->waitForSelector('#frame1');

        $frame = $this->page->frameLocator('#frame1');
        $frame->locator('#frame-input')->fill('test input');

        $value = $frame->locator('#frame-input')->inputValue();
        self::assertSame('test input', $value);
    }

    public function testCanAccessNestedFrames(): void
    {
        $this->goto('/frames.html');

        $this->page->waitForSelector('#parent-frame');

        $parentFrame = $this->page->frameLocator('#parent-frame');

        $parentHeading = $parentFrame->locator('#parent-heading')->textContent();
        self::assertSame('Parent Frame', $parentHeading);

        $childFrame = $parentFrame->frameLocator('#child-frame');

        $childHeading = $childFrame->locator('#frame-heading')->textContent();
        self::assertSame('This is frame content', $childHeading);
    }

    public function testCanGetAllFrames(): void
    {
        $this->goto('/frames.html');

        $this->page->waitForSelector('#frame1');
        $this->page->waitForSelector('#parent-frame');

        $frames = $this->page->frames();

        self::assertGreaterThanOrEqual(1, count($frames));
    }

    public function testCanWaitForDynamicFrame(): void
    {
        $this->goto('/frames.html');

        $this->page->click('#load-frame');

        $this->page->waitForSelector('#dynamic-frame');

        $frame = $this->page->frameLocator('#dynamic-frame');
        $heading = $frame->locator('#frame-heading')->textContent();
        self::assertSame('This is frame content', $heading);
    }

    public function testFrameLocatorBySelector(): void
    {
        $this->goto('/frames.html');

        $this->page->waitForSelector('#frame1');

        $frame = $this->page->frameLocator('iframe[name="frame1"]');

        $heading = $frame->locator('#frame-heading')->textContent();
        self::assertSame('This is frame content', $heading);
    }
}
