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

namespace Playwright\Tests\Integration\WebStorage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tests\Support\RouteServerTestTrait;
use Playwright\WebStorage\WebStorage;

#[CoversClass(WebStorage::class)]
class WebStorageTest extends TestCase
{
    use PlaywrightTestCaseTrait;
    use RouteServerTestTrait;

    public static function setUpBeforeClass(): void
    {
    }

    public static function tearDownAfterClass(): void
    {
    }

    public function setUp(): void
    {
        $this->setUpPlaywright();
        $this->installRouteServer($this->page, [
            '/index.html' => '<!DOCTYPE html><html><body><h1>Storage</h1></body></html>',
        ]);
        $this->page->goto($this->routeUrl('/index.html'));
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itSetsAndReadsAnItem(): void
    {
        $this->page->localStorage->setItem('token', 'abc');

        $this->assertSame('abc', $this->page->localStorage->getItem('token'));
    }

    #[Test]
    public function itReturnsNullForAnAbsentItem(): void
    {
        $this->assertNull($this->page->localStorage->getItem('missing'));
    }

    #[Test]
    public function itOverwritesAnExistingItem(): void
    {
        $this->page->localStorage->setItem('token', 'first');
        $this->page->localStorage->setItem('token', 'second');

        $this->assertSame('second', $this->page->localStorage->getItem('token'));
    }

    #[Test]
    public function itListsEveryItem(): void
    {
        $this->page->localStorage->setItem('a', '1');
        $this->page->localStorage->setItem('b', '2');

        $items = $this->page->localStorage->items();

        $this->assertCount(2, $items);
        $names = array_column($items, 'value', 'name');
        $this->assertSame(['a' => '1', 'b' => '2'], $names);
    }

    #[Test]
    public function itReturnsAnEmptyListWhenStorageIsEmpty(): void
    {
        $this->assertSame([], $this->page->localStorage->items());
    }

    #[Test]
    public function itRemovesAnItem(): void
    {
        $this->page->localStorage->setItem('a', '1');
        $this->page->localStorage->setItem('b', '2');

        $this->page->localStorage->removeItem('a');

        $this->assertNull($this->page->localStorage->getItem('a'));
        $this->assertSame('2', $this->page->localStorage->getItem('b'));
    }

    #[Test]
    public function itIgnoresRemovingAnAbsentItem(): void
    {
        $this->page->localStorage->removeItem('never-set');

        $this->assertSame([], $this->page->localStorage->items());
    }

    #[Test]
    public function itClearsEveryItem(): void
    {
        $this->page->localStorage->setItem('a', '1');
        $this->page->localStorage->setItem('b', '2');

        $this->page->localStorage->clear();

        $this->assertSame([], $this->page->localStorage->items());
    }

    #[Test]
    public function itKeepsLocalAndSessionStorageApart(): void
    {
        $this->page->localStorage->setItem('shared', 'local');
        $this->page->sessionStorage->setItem('shared', 'session');

        $this->assertSame('local', $this->page->localStorage->getItem('shared'));
        $this->assertSame('session', $this->page->sessionStorage->getItem('shared'));

        $this->page->localStorage->clear();

        $this->assertSame([], $this->page->localStorage->items());
        $this->assertSame([['name' => 'shared', 'value' => 'session']], $this->page->sessionStorage->items());
    }

    #[Test]
    public function itExposesTheSameStorageThroughTheInterfaceAccessors(): void
    {
        $this->assertSame($this->page->localStorage, $this->page->localStorage());
        $this->assertSame($this->page->sessionStorage, $this->page->sessionStorage());

        $this->page->localStorage()->setItem('via-accessor', 'yes');

        $this->assertSame('yes', $this->page->localStorage->getItem('via-accessor'));
    }

    #[Test]
    public function itWritesValuesThePageCanRead(): void
    {
        $this->page->localStorage->setItem('from-php', 'hello');

        $this->assertSame('hello', $this->page->evaluate('() => window.localStorage.getItem("from-php")'));
    }

    #[Test]
    public function itReadsValuesThePageWrote(): void
    {
        $this->page->evaluate('() => window.sessionStorage.setItem("from-js", "world")');

        $this->assertSame('world', $this->page->sessionStorage->getItem('from-js'));
    }
}
