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

namespace Playwright\Tests\Functional\Injection;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
final class CssInjectionTest extends FunctionalTestCase
{
    public function testCanAddStyleTag(): void
    {
        $this->goto('/injection.html');

        $this->page->addStyleTag(['content' => '#test-element { background-color: rgb(255, 0, 0); }']);

        $color = $this->page->evaluate('window.getComputedStyle(document.getElementById("test-element")).backgroundColor');

        self::assertSame('rgb(255, 0, 0)', $color);
    }

    public function testCanAddStyleTagFromUrl(): void
    {
        $this->goto('/injection.html');

        $cssContent = '#test-element { color: rgb(0, 0, 255); }';
        $dataUrl = 'data:text/css;base64,'.\base64_encode($cssContent);

        $this->page->addStyleTag(['url' => $dataUrl]);

        $color = $this->page->evaluate('window.getComputedStyle(document.getElementById("test-element")).color');

        self::assertSame('rgb(0, 0, 255)', $color);
    }

    public function testCanInjectMultipleStyleTags(): void
    {
        $this->goto('/injection.html');

        $this->page->addStyleTag(['content' => '#test-element { font-size: 24px; }']);
        $this->page->addStyleTag(['content' => '#test-element { font-weight: bold; }']);

        $fontSize = $this->page->evaluate('window.getComputedStyle(document.getElementById("test-element")).fontSize');
        $fontWeight = $this->page->evaluate('window.getComputedStyle(document.getElementById("test-element")).fontWeight');

        self::assertSame('24px', $fontSize);
        self::assertStringContainsString('700', $fontWeight);
    }

    public function testCanOverrideExistingStyles(): void
    {
        $this->goto('/injection.html');

        $originalBg = $this->page->evaluate('window.getComputedStyle(document.getElementById("test-element")).backgroundColor');

        $this->page->addStyleTag(['content' => '#test-element { background-color: rgb(0, 255, 0) !important; }']);

        $newBg = $this->page->evaluate('window.getComputedStyle(document.getElementById("test-element")).backgroundColor');

        self::assertNotSame($originalBg, $newBg);
        self::assertSame('rgb(0, 255, 0)', $newBg);
    }

    public function testCanInjectComplexCss(): void
    {
        $this->goto('/injection.html');

        $css = <<<'CSS'
#test-element {
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transform: rotate(5deg);
}
CSS;

        $this->page->addStyleTag(['content' => $css]);

        $borderRadius = $this->page->evaluate('window.getComputedStyle(document.getElementById("test-element")).borderRadius');

        self::assertSame('10px', $borderRadius);
    }
}
