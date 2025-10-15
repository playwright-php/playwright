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

namespace Playwright\Tests\Functional\Interactions;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Locator\Locator;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Locator::class)]
final class FormInteractionTest extends FunctionalTestCase
{
    public function testCanFillTextInput(): void
    {
        $this->goto('/forms.html');

        $this->page->locator('#name')->fill('John Doe');

        $value = $this->page->locator('#name')->inputValue();
        self::assertSame('John Doe', $value);
    }

    public function testCanSelectOption(): void
    {
        $this->goto('/forms.html');

        $this->page->locator('#color')->selectOption('blue');

        $value = $this->page->locator('#color')->inputValue();
        self::assertSame('blue', $value);
    }

    public function testCanCheckCheckbox(): void
    {
        $this->goto('/forms.html');

        $checkbox = $this->page->locator('#agree');
        self::assertFalse($checkbox->isChecked());

        $checkbox->check();

        self::assertTrue($checkbox->isChecked());
    }

    public function testCanUncheckCheckbox(): void
    {
        $this->goto('/forms.html');

        $this->page->locator('#agree')->check();
        self::assertTrue($this->page->locator('#agree')->isChecked());

        $this->page->locator('#agree')->uncheck();

        self::assertFalse($this->page->locator('#agree')->isChecked());
    }

    public function testCanSelectRadioButton(): void
    {
        $this->goto('/forms.html');

        $this->page->locator('input[name="size"][value="medium"]')->check();

        $mediumRadio = $this->page->locator('input[name="size"][value="medium"]');
        self::assertTrue($mediumRadio->isChecked());
    }

    public function testCanFillTextarea(): void
    {
        $this->goto('/forms.html');

        $this->page->locator('#comment')->fill('This is a test comment');

        $value = $this->page->locator('#comment')->inputValue();
        self::assertSame('This is a test comment', $value);
    }

    public function testCanSubmitForm(): void
    {
        $this->goto('/forms.html');

        $this->page->locator('#name')->fill('Jane Smith');
        $this->page->locator('#color')->selectOption('red');
        $this->page->click('#submit-btn');

        $this->assertUrlContains('form-submit.html');
        $this->assertUrlContains('name=Jane');
    }
}
