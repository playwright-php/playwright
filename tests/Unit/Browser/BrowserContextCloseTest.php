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

namespace Playwright\Tests\Unit\Browser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\Browser;
use Playwright\Browser\BrowserContext;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\TransportException;
use Playwright\Page\Page;
use Playwright\Tests\Mocks\EventDispatcherTransport;
use Playwright\Transport\MockTransport;

#[CoversClass(Browser::class)]
#[CoversClass(BrowserContext::class)]
#[CoversClass(Page::class)]
final class BrowserContextCloseTest extends TestCase
{
    private MockTransport $mockTransport;
    private EventDispatcherTransport $transport;
    private Browser $browser;

    protected function setUp(): void
    {
        $this->mockTransport = new MockTransport();
        $this->mockTransport->connect();
        $this->transport = new EventDispatcherTransport($this->mockTransport);
        $this->browser = new Browser($this->transport, 'browser_1', 'ctx_default', '1.0', new PlaywrightConfig());
    }

    public function testCloseForgetsTheContextAndReleasesItsDispatchers(): void
    {
        $context = $this->openContext('ctx_1');
        $this->mockTransport->queueResponse(['pageId' => 'page_1']);
        $context->newPage();
        $this->mockTransport->queueResponse([]);
        $context->route('**/*', static function (): void {});

        $this->assertSame([$this->browser->context(), $context], $this->browser->contexts());
        $this->assertSame(['ctx_default', 'ctx_1', 'page_1'], array_keys($this->transport->getEventDispatchers()));

        $this->mockTransport->queueResponse([]);
        $context->close();

        $this->assertSame([$this->browser->context()], $this->browser->contexts());
        $this->assertSame(['ctx_default'], array_keys($this->transport->getEventDispatchers()));
        $this->assertSame([], $context->pages());
        $this->assertSame(
            ['newContext', 'context.newPage', 'context.route', 'context.close'],
            array_column($this->mockTransport->getSentMessages(), 'action'),
        );
    }

    public function testClosedContextsAreGarbageCollected(): void
    {
        $references = [];
        for ($i = 0; $i < 3; ++$i) {
            $context = $this->openContext('ctx_'.$i);
            $this->mockTransport->queueResponse(['pageId' => 'page_'.$i]);
            $page = $context->newPage();
            $this->mockTransport->queueResponse([]);
            $page->route('**/*', static fn () => $context);
            $this->mockTransport->queueResponse([]);
            $context->route('**/*', static fn () => $page);
            $this->mockTransport->queueResponse(['popupPageId' => 'popup_'.$i]);
            $popup = $page->waitForPopup(static fn () => null);
            $this->mockTransport->queueResponse([]);
            $popup->route('**/*', static fn () => $context);

            $this->mockTransport->queueResponse([]);
            $context->close();

            $references[] = \WeakReference::create($context);
        }
        unset($context, $page, $popup);

        gc_collect_cycles();

        foreach ($references as $reference) {
            $this->assertNull($reference->get());
        }
    }

    public function testCloseReleasesTheContextWhenTheCloseCommandFails(): void
    {
        $context = $this->openContext('ctx_1');
        $this->mockTransport->queueResponse(['pageId' => 'page_1']);
        $context->newPage();

        $this->mockTransport->queueResponse(new TransportException('context.close failed'));
        try {
            $context->close();
            $this->fail('Expected the close command to fail.');
        } catch (TransportException) {
        }

        $this->assertSame([$this->browser->context()], $this->browser->contexts());
        $this->assertSame(['ctx_default'], array_keys($this->transport->getEventDispatchers()));
        $this->assertSame([], $context->pages());
    }

    public function testPopupIsTrackedByItsContext(): void
    {
        $context = $this->openContext('ctx_1');
        $this->mockTransport->queueResponse(['pageId' => 'page_1']);
        $page = $context->newPage();
        $this->mockTransport->queueResponse(['popupPageId' => 'popup_1']);

        $popup = $page->waitForPopup(static fn () => null);

        $this->assertSame([$page, $popup], $context->pages());

        $this->mockTransport->queueResponse([]);
        $context->close();

        $this->assertSame(['ctx_default'], array_keys($this->transport->getEventDispatchers()));
    }

    public function testPageCloseReleasesThePage(): void
    {
        $context = $this->openContext('ctx_1');
        $this->mockTransport->queueResponse(['pageId' => 'page_1']);
        $page = $context->newPage();

        $this->mockTransport->queueResponse([]);
        $page->close();

        $this->assertSame([], $context->pages());
        $this->assertSame(['ctx_default', 'ctx_1'], array_keys($this->transport->getEventDispatchers()));
        $this->assertSame(
            ['newContext', 'context.newPage', 'page.close'],
            array_column($this->mockTransport->getSentMessages(), 'action'),
        );
    }

    public function testPageClosedEventReleasesThePageDispatcher(): void
    {
        $context = $this->openContext('ctx_1');
        $this->mockTransport->queueResponse(['pageId' => 'page_1']);
        $context->newPage();

        $context->dispatchEvent('pageClosed', ['pageId' => 'page_1']);

        $this->assertSame([], $context->pages());
        $this->assertSame(['ctx_default', 'ctx_1'], array_keys($this->transport->getEventDispatchers()));
    }

    private function openContext(string $contextId): BrowserContext
    {
        $this->mockTransport->queueResponse(['contextId' => $contextId]);

        return $this->browser->newContext();
    }
}
