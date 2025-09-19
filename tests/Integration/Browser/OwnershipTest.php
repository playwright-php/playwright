<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Integration\Browser;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Browser\Browser;
use PlaywrightPHP\Browser\BrowserContext;
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Internal\OwnershipRegistry;
use PlaywrightPHP\Page\Page;
use PlaywrightPHP\Transport\TransportInterface;
use Psr\Log\NullLogger;

class OwnershipTest extends TestCase
{
    private TransportInterface $transport;
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->logger = new NullLogger();
        OwnershipRegistry::reset();
    }

    protected function tearDown(): void
    {
        OwnershipRegistry::reset();
    }

    #[Test]
    public function itCreatesBrowserContextPageHierarchy(): void
    {
        // Mock transport responses
        $this->transport->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                ['contextId' => 'new-context-123'], // newContext response
                ['pageId' => 'new-page-123']        // newPage response
            );

        $browser = new Browser($this->transport, 'browser-123', 'default-context-123', '1.0');
        $context = $browser->newContext();
        $page = $context->newPage();

        // Verify hierarchy
        $this->assertFalse($browser->isDisposed());
        $this->assertFalse($context->isDisposed());
        $this->assertFalse($page->isDisposed());

        // Verify parent-child relationships through ownership registry
        $browserRemote = $browser->getRemoteObject();
        $contextRemote = $context->getRemoteObject();
        $pageRemote = $page->getRemoteObject();

        // Browser should have contexts as children
        $this->assertContains($contextRemote, $browserRemote->getChildren());
        
        // Context should have browser as parent and page as child
        $this->assertSame($browserRemote, $contextRemote->getParent());
        $this->assertContains($pageRemote, $contextRemote->getChildren());
        
        // Page should have context as parent
        $this->assertSame($contextRemote, $pageRemote->getParent());
    }

    #[Test]
    public function itCascadesDisposalFromBrowserToChildren(): void
    {
        // Mock transport responses for creation
        $this->transport->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                ['contextId' => 'new-context-123'], // newContext response
                ['pageId' => 'new-page-123']        // newPage response
            );

        $browser = new Browser($this->transport, 'browser-123', 'default-context-123', '1.0');
        $context = $browser->newContext();
        $page = $context->newPage();

        $this->assertFalse($browser->isDisposed());
        $this->assertFalse($context->isDisposed());
        $this->assertFalse($page->isDisposed());

        // Closing browser should cascade to all children
        $browser->close();

        $this->assertTrue($browser->isDisposed());
        $this->assertTrue($context->isDisposed());
        $this->assertTrue($page->isDisposed());
    }

    #[Test]
    public function itIsIdempotentWhenClosingMultipleTimes(): void
    {
        $browser = new Browser($this->transport, 'browser-123', 'default-context-123', '1.0');

        $this->assertFalse($browser->isDisposed());

        $browser->close();
        $this->assertTrue($browser->isDisposed());

        // Should not throw or cause issues
        $browser->close();
        $this->assertTrue($browser->isDisposed());
    }

    #[Test]
    public function itHandlesIndividualPageDisposal(): void
    {
        // Mock transport responses
        $this->transport->expects($this->once())
            ->method('send')
            ->willReturn(['pageId' => 'new-page-123']);

        $context = new BrowserContext($this->transport, 'context-123');
        $page = $context->newPage();

        $this->assertFalse($context->isDisposed());
        $this->assertFalse($page->isDisposed());

        // Closing page should not affect context
        $page->close();

        $this->assertFalse($context->isDisposed());
        $this->assertTrue($page->isDisposed());

        // Page should be removed from context's children
        $contextRemote = $context->getRemoteObject();
        $pageRemote = $page->getRemoteObject();
        
        $this->assertNotContains($pageRemote, $contextRemote->getChildren());
        $this->assertNull($pageRemote->getParent());
    }
}
