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

namespace Playwright\Tests\Integration\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Page\Options\PageErrorsOptions;
use Playwright\Page\Page;
use Playwright\PageError\PageErrorInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;

#[CoversClass(Page::class)]
final class PageErrorsTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    protected function setUp(): void
    {
        $this->setUpPlaywright();
    }

    protected function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    public function testItReadsRecordedErrorsWithoutConsumingThem(): void
    {
        $this->assertSame([], $this->page->pageErrors());
        $this->page->setContent(<<<'HTML'
            <script>function fail() { throw new TypeError('first failure'); } fail();</script>
            <script>throw new RangeError('second failure');</script>
            HTML);

        $errors = $this->page->pageErrors();

        $this->assertCount(2, $errors);
        $this->assertInstanceOf(PageErrorInterface::class, $errors[0]);
        $this->assertSame('TypeError', $errors[0]->name());
        $this->assertSame('first failure', $errors[0]->message());
        $this->assertNotNull($errors[0]->stack());
        $this->assertStringContainsString('TypeError: first failure', $errors[0]->stack());
        $this->assertStringContainsString('at fail', $errors[0]->stack());
        $this->assertSame('RangeError', $errors[1]->name());
        $this->assertSame('second failure', $errors[1]->message());
        $this->assertEquals($errors, $this->page->pageErrors());
    }

    public function testItFiltersErrorsAcrossNavigations(): void
    {
        $this->page->goto('data:text/html,<script>throw new Error("before navigation");</script>');
        $this->assertCount(1, $this->page->pageErrors());
        $this->page->goto('data:text/html,<script>throw new TypeError("after navigation");</script>');

        $all = $this->page->pageErrors(['filter' => 'all']);
        $current = $this->page->pageErrors(new PageErrorsOptions(filter: 'since-navigation'));

        $this->assertSame(['before navigation', 'after navigation'], array_map(static fn (PageErrorInterface $error): string => $error->message(), $all));
        $this->assertCount(1, $current);
        $this->assertSame('after navigation', $current[0]->message());
        $this->assertEquals($current, $this->page->pageErrors());
    }

    public function testItClearsAllErrorsAndContinuesRecording(): void
    {
        $this->page->goto('data:text/html,<script>throw new Error("old failure");</script>');
        $this->page->goto('data:text/html,<script>throw new Error("current failure");</script>');
        $snapshot = $this->page->pageErrors(['filter' => 'all']);
        $this->assertCount(2, $snapshot);

        $this->assertSame($this->page, $this->page->clearPageErrors());
        $this->assertSame([], $this->page->pageErrors(['filter' => 'all']));
        $this->assertSame([], $this->page->pageErrors());
        $this->assertSame('old failure', $snapshot[0]->message());

        $this->page->setContent('<script>throw new Error("new failure");</script>');
        $errors = $this->page->pageErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('new failure', $errors[0]->message());
    }

    public function testConsoleErrorsDoNotBecomePageErrors(): void
    {
        $this->page->evaluate('() => console.error("logged failure")');

        $this->assertSame([], $this->page->pageErrors());
        $this->assertCount(1, $this->page->consoleMessages());

        $this->page->setContent('<script>throw new Error("uncaught failure");</script>');

        $this->assertCount(1, $this->page->pageErrors());
        $messages = $this->page->consoleMessages();
        $this->assertCount(1, $messages);
        $this->assertSame('logged failure', $messages[0]->text());
    }

    public function testHistoriesBelongToTheirPage(): void
    {
        $other = $this->context->newPage();
        $this->page->setContent('<script>throw new Error("first page");</script>');

        $this->assertSame([], $other->pageErrors());
        $other->setContent('<script>throw new Error("other page");</script>');
        $this->page->clearPageErrors();

        $this->assertSame([], $this->page->pageErrors());
        $errors = $other->pageErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('other page', $errors[0]->message());
    }
}
