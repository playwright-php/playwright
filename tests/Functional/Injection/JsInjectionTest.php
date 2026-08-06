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
final class JsInjectionTest extends FunctionalTestCase
{
    public function testCanAddScriptTag(): void
    {
        $this->goto('/injection.html');

        $this->page->addScriptTag(['content' => 'window.injectedValue = 123;']);

        $value = $this->page->evaluate('window.injectedValue');

        $this->assertSame(123, $value);
    }

    public function testCanAddScriptTagFromUrl(): void
    {
        $this->goto('/injection.html');

        $jsContent = 'window.fromUrl = "loaded";';
        $dataUrl = 'data:text/javascript;base64,'.\base64_encode($jsContent);

        $this->page->addScriptTag(['url' => $dataUrl]);

        $value = $this->page->evaluate('window.fromUrl');

        $this->assertSame('loaded', $value);
    }

    public function testCanInjectFunction(): void
    {
        $this->goto('/injection.html');

        $this->page->addScriptTag(['content' => 'window.multiply = (a, b) => a * b;']);

        $result = $this->page->evaluate('window.multiply(5, 7)');

        $this->assertSame(35, $result);
    }

    public function testCanInjectComplexScript(): void
    {
        $this->goto('/injection.html');

        $script = <<<'JS'
window.utils = {
    sum: (...numbers) => numbers.reduce((a, b) => a + b, 0),
    average: (...numbers) => window.utils.sum(...numbers) / numbers.length,
    max: (...numbers) => Math.max(...numbers)
};
JS;

        $this->page->addScriptTag(['content' => $script]);

        $sum = $this->page->evaluate('window.utils.sum(1, 2, 3, 4, 5)');
        $avg = $this->page->evaluate('window.utils.average(10, 20, 30)');
        $max = $this->page->evaluate('window.utils.max(5, 15, 10)');

        $this->assertSame(15, $sum);
        $this->assertSame(20, $avg);
        $this->assertSame(15, $max);
    }

    public function testCanAccessExistingWindowObjects(): void
    {
        $this->goto('/injection.html');

        $this->page->addScriptTag(['content' => 'window.combined = window.testData.value * 2;']);

        $value = $this->page->evaluate('window.combined');

        $this->assertSame(84, $value);
    }

    public function testCanManipulateDOM(): void
    {
        $this->goto('/injection.html');

        $script = <<<'JS'
const div = document.createElement('div');
div.id = 'injected-element';
div.textContent = 'Injected by script';
document.getElementById('dynamic-content').appendChild(div);
JS;

        $this->page->addScriptTag(['content' => $script]);

        $text = $this->page->locator('#injected-element')->textContent();

        $this->assertSame('Injected by script', $text);
    }

    public function testCanInjectMultipleScripts(): void
    {
        $this->goto('/injection.html');

        $this->page->addScriptTag(['content' => 'window.step1 = 10;']);
        $this->page->addScriptTag(['content' => 'window.step2 = window.step1 + 20;']);
        $this->page->addScriptTag(['content' => 'window.step3 = window.step2 * 2;']);

        $result = $this->page->evaluate('window.step3');

        $this->assertSame(60, $result);
    }

    public function testCanEvaluateInlineExpression(): void
    {
        $this->goto('/injection.html');

        $result = $this->page->evaluate('2 + 2');

        $this->assertSame(4, $result);
    }

    public function testCanEvaluateWithArguments(): void
    {
        $this->goto('/injection.html');

        $result = $this->page->evaluate('(arg) => arg.x * arg.y', ['x' => 10, 'y' => 5]);

        $this->assertSame(50, $result);
    }
}
