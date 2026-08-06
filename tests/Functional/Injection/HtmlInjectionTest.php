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
final class HtmlInjectionTest extends FunctionalTestCase
{
    public function testCanSetContent(): void
    {
        $this->goto('/injection.html');

        $html = '<h1 id="new-heading">New Content</h1><p id="new-paragraph">This is injected HTML</p>';

        $this->page->setContent($html);

        $heading = $this->page->locator('#new-heading')->textContent();
        $paragraph = $this->page->locator('#new-paragraph')->textContent();

        $this->assertSame('New Content', $heading);
        $this->assertSame('This is injected HTML', $paragraph);
    }

    public function testCanSetCompleteHtmlDocument(): void
    {
        $this->goto('/injection.html');

        $html = <<<'HTML'
<!doctype html>
<html>
<head>
    <title>Injected Page</title>
</head>
<body>
    <div id="injected-content">Complete HTML document</div>
</body>
</html>
HTML;

        $this->page->setContent($html);

        $title = $this->page->title();
        $content = $this->page->locator('#injected-content')->textContent();

        $this->assertSame('Injected Page', $title);
        $this->assertSame('Complete HTML document', $content);
    }

    public function testCanInjectHtmlWithStyles(): void
    {
        $this->goto('/injection.html');

        $html = <<<'HTML'
<style>
    #styled-element { color: rgb(255, 0, 0); font-size: 20px; }
</style>
<div id="styled-element">Styled content</div>
HTML;

        $this->page->setContent($html);

        $color = $this->page->evaluate('window.getComputedStyle(document.getElementById("styled-element")).color');

        $this->assertSame('rgb(255, 0, 0)', $color);
    }

    public function testCanInjectHtmlWithScripts(): void
    {
        $this->goto('/injection.html');

        $html = <<<'HTML'
<div id="target"></div>
<script>
    document.getElementById('target').textContent = 'Script executed';
    window.scriptRan = true;
</script>
HTML;

        $this->page->setContent($html);

        $text = $this->page->locator('#target')->textContent();
        $scriptRan = $this->page->evaluate('window.scriptRan');

        $this->assertSame('Script executed', $text);
        $this->assertTrue($scriptRan);
    }

    public function testCanGetCurrentContent(): void
    {
        $this->goto('/injection.html');

        $content = $this->page->content();

        $this->assertStringContainsString('Injection Tests', $content);
        $this->assertStringContainsString('<!doctype html>', \strtolower($content));
    }

    public function testCanGetInnerHtml(): void
    {
        $this->goto('/injection.html');

        $innerHTML = $this->page->locator('#test-element')->innerHTML();

        $this->assertStringContainsString('Original Content', $innerHTML);
    }

    public function testCanSetComplexHtmlStructure(): void
    {
        $this->goto('/injection.html');

        $html = <<<'HTML'
<div id="container">
    <ul id="list">
        <li class="item">Item 1</li>
        <li class="item">Item 2</li>
        <li class="item">Item 3</li>
    </ul>
    <button id="action-btn">Click Me</button>
</div>
HTML;

        $this->page->setContent($html);

        $itemCount = $this->page->locator('.item')->count();
        $btnText = $this->page->locator('#action-btn')->textContent();

        $this->assertSame(3, $itemCount);
        $this->assertSame('Click Me', $btnText);
    }

    public function testCanInjectFormsAndInputs(): void
    {
        $this->goto('/injection.html');

        $html = <<<'HTML'
<form id="test-form">
    <input type="text" id="username" name="username" value="testuser">
    <input type="email" id="email" name="email" value="test@example.com">
    <button type="submit">Submit</button>
</form>
HTML;

        $this->page->setContent($html);

        $usernameValue = $this->page->locator('#username')->inputValue();
        $emailValue = $this->page->locator('#email')->inputValue();

        $this->assertSame('testuser', $usernameValue);
        $this->assertSame('test@example.com', $emailValue);
    }
}
