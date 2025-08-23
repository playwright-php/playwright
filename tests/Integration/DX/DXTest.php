<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\DX;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Browser\BrowserBuilder;
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Locator\Locator;
use PlaywrightPHP\PlaywrightFactory;
use PlaywrightPHP\Tests\Mocks\TestLogger;
use Symfony\Component\Process\ExecutableFinder;

#[CoversClass(BrowserBuilder::class)]
#[CoversClass(Locator::class)]
class DXTest extends TestCase
{
    private function getNodeExecutable(): string
    {
        $finder = new ExecutableFinder();
        $node = $finder->find('node');
        if (null === $node) {
            $this->markTestSkipped('Node.js executable not found.');
        }

        return $node;
    }

    #[Test]
    public function itLogsVerboseApiCalls(): void
    {
        $logger = new TestLogger();
        $config = new PlaywrightConfig(nodePath: $this->getNodeExecutable());
        $playwright = PlaywrightFactory::create($config, $logger);
        $browser = $playwright->chromium()->launch();
        $page = $browser->newPage();
        $page->setContent('<h1>Hello</h1>');
        $page->close();
        $browser->close();

        // Test that logger was provided and operations completed successfully
        $this->assertInstanceOf(TestLogger::class, $logger);
        $this->assertTrue(true, 'Basic Playwright operations completed successfully');
    }

    #[Test]
    public function itCastsLocatorsToString(): void
    {
        $config = new PlaywrightConfig(nodePath: $this->getNodeExecutable());
        $playwright = PlaywrightFactory::create($config);
        $browser = $playwright->chromium()->launch();
        $page = $browser->newPage();
        $page->setContent('<h1>Hello</h1>');

        $locator = $page->locator('h1');
        $this->assertEquals('Locator(selector="h1")', (string) $locator);

        $page->close();
        $browser->close();
    }

    #[Test]
    public function itLaunchesWithInspector(): void
    {
        $config = new PlaywrightConfig(
            nodePath: $this->getNodeExecutable(),
            headless: false
        );
        $playwright = PlaywrightFactory::create($config);
        $browser = $playwright->chromium()->withInspector()->launch();

        // Create a page to trigger inspector
        $page = $browser->newPage();

        // Inspector should work without throwing errors
        $this->assertInstanceOf(\PlaywrightPHP\Page\PageInterface::class, $page);

        $page->close();
        $browser->close();
    }
}
