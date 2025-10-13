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

namespace Playwright\Tests\Functional\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Input\Keyboard;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Keyboard::class)]
final class KeyboardTest extends FunctionalTestCase
{
    public function testCanTypeText(): void
    {
        $this->goto('/keyboard.html');

        $this->page->locator('#input-field')->fill('Hello World');

        $value = $this->page->locator('#input-value')->textContent();
        self::assertSame('Value: Hello World', $value);
    }

    public function testCanTypeCharacters(): void
    {
        $this->goto('/keyboard.html');

        $input = $this->page->locator('#key-listener');
        $input->click();
        $input->type('ABC');

        $lastKey = $this->page->locator('#last-key')->textContent();
        self::assertNotEmpty($lastKey);
    }

    public function testCanClearInput(): void
    {
        $this->goto('/keyboard.html');

        $input = $this->page->locator('#key-listener');
        $input->fill('Test');
        $input->clear();

        $value = $input->inputValue();
        self::assertSame('', $value);
    }

    public function testCanClearTextarea(): void
    {
        $this->goto('/keyboard.html');

        $editor = $this->page->locator('#editor');

        $editor->fill('');

        $value = $editor->inputValue();
        self::assertSame('', $value);

        $info = $this->page->locator('#editor-info')->textContent();
        self::assertSame('Length: 0', $info);
    }
}
