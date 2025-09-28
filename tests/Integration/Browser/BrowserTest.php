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

namespace Playwright\Tests\Integration\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\Browser;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Page\PageInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;

#[CoversClass(Browser::class)]
class BrowserTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    public function setUp(): void
    {
        $this->setUpPlaywright();
    }

    public function tearDown(): void
    {
        $this->tearDownPlaywright();
    }

    #[Test]
    public function itReturnsTheBrowserVersion(): void
    {
        $this->assertNotEmpty($this->browser->version());
        $this->assertIsString($this->browser->version());
    }

    #[Test]
    public function itChecksTheConnectionStatus(): void
    {
        $this->assertTrue($this->browser->isConnected());
        $this->browser->close();
        $this->assertFalse($this->browser->isConnected());
    }

    #[Test]
    public function itCreatesANewBrowserContext(): void
    {
        $context = $this->browser->newContext();
        $this->assertInstanceOf(BrowserContextInterface::class, $context);
        $context->close();
    }

    #[Test]
    public function itRetrievesAllBrowserContexts(): void
    {
        $initialCount = count($this->browser->contexts());

        $context1 = $this->browser->newContext();
        $this->assertCount($initialCount + 1, $this->browser->contexts());

        $context2 = $this->browser->newContext();
        $this->assertCount($initialCount + 2, $this->browser->contexts());

        $context1->close();
        $context2->close();
    }

    #[Test]
    public function itCreatesANewPage(): void
    {
        $page = $this->browser->newPage();
        $this->assertInstanceOf(PageInterface::class, $page);
        $page->close();
    }
}
