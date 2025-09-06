<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Frame;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Frame\Frame;
use PlaywrightPHP\Frame\FrameInterface;
use PlaywrightPHP\Locator\LocatorInterface;
use PlaywrightPHP\Transport\TransportInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(Frame::class)]
class FrameTest extends TestCase
{
    private MockObject|TransportInterface $transport;
    private MockObject|LoggerInterface $logger;
    private string $pageId = 'page-id-1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->transport = $this->createMock(TransportInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testLocatorAndOwner(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $locator = $frame->locator('button');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('button', $locator->getSelector());

        $owner = $frame->owner();
        $this->assertInstanceOf(LocatorInterface::class, $owner);
        $this->assertSame('iframe#auth', $owner->getSelector());
    }

    public function testFrameLocator(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $child = $frame->frameLocator('iframe[name="nested"]');
        $this->assertSame('FrameLocator(selector="iframe#auth >> iframe[name="nested"]")', (string) $child);
    }

    public function testNameUrlAndDetached(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->transport->expects($this->exactly(3))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                ['value' => 'auth-frame'],
                ['value' => 'https://example.com/'],
                ['value' => true]
            );

        $this->assertSame('auth-frame', $frame->name());
        $this->assertSame('https://example.com/', $frame->url());
        $this->assertTrue($frame->isDetached());
    }

    public function testWaitForLoadState(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) {
                return 'frame.waitForLoadState' === $payload['action']
                    && $payload['pageId'] === $this->pageId
                    && 'iframe#auth' === $payload['frameSelector']
                    && 'domcontentloaded' === $payload['state'];
            }))
            ->willReturn(['success' => true]);

        $this->assertSame($frame, $frame->waitForLoadState('domcontentloaded'));
    }

    public function testParentFrame(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#child', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) {
                return 'frame.parent' === $payload['action']
                    && 'iframe#child' === $payload['frameSelector'];
            }))
            ->willReturn(['selector' => ':root']);

        $parent = $frame->parentFrame();
        $this->assertInstanceOf(FrameInterface::class, $parent);
        $this->assertSame('Frame(selector=":root")', (string) $parent);
    }

    public function testChildFrames(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#parent', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) {
                return 'frame.children' === $payload['action']
                    && 'iframe#parent' === $payload['frameSelector'];
            }))
            ->willReturn(['frames' => [
                ['selector' => 'iframe#parent >> iframe#child1'],
                ['selector' => 'iframe#parent >> iframe#child2'],
            ]]);

        $children = $frame->childFrames();
        $this->assertCount(2, $children);
        $this->assertInstanceOf(FrameInterface::class, $children[0]);
        $this->assertSame('Frame(selector="iframe#parent >> iframe#child1")', (string) $children[0]);
    }
}
