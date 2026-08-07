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

namespace Playwright\Tests\Integration\Selector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Playwright;
use Playwright\Selector\Selectors;
use Playwright\Selector\SelectorsInterface;

/**
 * The facade hands out one selector registry for every browser it launches, so an
 * engine registered through it must be usable from a context it creates later.
 */
#[CoversClass(Playwright::class)]
#[CoversClass(Selectors::class)]
class PlaywrightFacadeSelectorsTest extends TestCase
{
    private ?BrowserContextInterface $context = null;

    protected function tearDown(): void
    {
        $this->context?->close();
        $this->context = null;
    }

    #[Test]
    public function itRegistersAnEngineUsableByABrowserItLaunches(): void
    {
        $script = <<<'JS'
            {
                query(root, selector) {
                    return root.querySelector(`[data-engine="${selector}"]`);
                },
                queryAll(root, selector) {
                    return Array.from(root.querySelectorAll(`[data-engine="${selector}"]`));
                }
            }
            JS;

        Playwright::selectors()->register('facade-engine', $script);

        $this->context = Playwright::chromium(['headless' => true]);
        $page = $this->context->newPage();
        $page->setContent('<div data-engine="target">engine hit</div>');

        $this->assertSame('engine hit', $page->locator('facade-engine=target')->textContent());
    }

    #[Test]
    public function itHandsOutOneRegistryForTheWholeFacade(): void
    {
        $selectors = Playwright::selectors();

        $this->assertInstanceOf(SelectorsInterface::class, $selectors);
        $this->assertSame($selectors, Playwright::selectors());
    }

    #[Test]
    public function itReadsBackTheTestIdAttribute(): void
    {
        Playwright::selectors()->setTestIdAttribute('data-facade-id');

        $this->assertSame('data-facade-id', Playwright::selectors()->getTestIdAttribute());
    }
}
