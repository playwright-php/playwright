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

namespace Playwright\Tests\Unit\Frame;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Exception\RuntimeException;
use Playwright\Frame\Frame;
use Playwright\Frame\FrameInterface;
use Playwright\JSHandle\JSHandleInterface;
use Playwright\Locator\LocatorInterface;
use Playwright\Network\ResponseInterface;
use Playwright\Page\Options\GotoOptions;
use Playwright\Page\Options\WaitForNavigationOptions;
use Playwright\Page\PageInterface;
use Playwright\Transport\TransportInterface;
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

    public function testEvaluateSendsExpressionAndArgument(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'expression' => '(arg) => arg.value * 2',
                'arg' => ['value' => 21],
                'action' => 'frame.evaluate',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['result' => 42]);

        $this->assertSame(42, $frame->evaluate('(arg) => arg.value * 2', ['value' => 21]));
    }

    public function testEvaluateNormalizesReturnExpression(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (array $payload): bool => '(arg) => { return arg.value; }' === $payload['expression']))
            ->willReturn(['result' => 'value']);

        $this->assertSame('value', $frame->evaluate('return arg.value;', ['value' => 'value']));
    }

    public function testContentReturnsNativeFrameContent(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'frame.content',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['content' => '<html><body>Authentication</body></html>']);

        $this->assertSame('<html><body>Authentication</body></html>', $frame->content());
    }

    public function testGotoReturnsNativeFrameResponse(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'url' => 'https://example.com/inside-frame',
                'options' => ['waitUntil' => 'domcontentloaded'],
                'action' => 'frame.goto',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['response' => [
                'responseId' => 'response-1',
                'url' => 'https://example.com/inside-frame',
                'status' => 200,
                'statusText' => 'OK',
                'headers' => [],
            ]]);

        $response = $frame->goto('https://example.com/inside-frame', new GotoOptions(waitUntil: 'domcontentloaded'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->status());
    }

    public function testSetContentSendsNativeFrameCommand(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'html' => '<h1>Updated</h1>',
                'options' => ['waitUntil' => 'load'],
                'action' => 'frame.setContent',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['success' => true]);

        $this->assertSame($frame, $frame->setContent('<h1>Updated</h1>', ['waitUntil' => 'load']));
    }

    public function testWaitForFunctionSendsNativeFrameCommand(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'pageFunction' => '(arg) => { return arg.ready; }',
                'arg' => ['ready' => true],
                'options' => ['timeout' => 500.0, 'polling' => 'raf'],
                'action' => 'frame.waitForFunction',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['success' => true]);

        $this->assertSame($frame, $frame->waitForFunction('return arg.ready;', ['ready' => true], ['timeout' => 500.0, 'polling' => 'raf']));
    }

    public function testWaitForUrlSendsNativeFrameCommand(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'url' => '**/complete.html',
                'options' => ['timeout' => 500.0, 'waitUntil' => 'commit'],
                'action' => 'frame.waitForURL',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['success' => true]);

        $this->assertSame($frame, $frame->waitForURL('**/complete.html', ['timeout' => 500.0, 'waitUntil' => 'commit']));
    }

    public function testWaitForNavigationReturnsNativeFrameResponse(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => ['url' => '**/complete.html', 'waitUntil' => 'commit'],
                'action' => 'frame.waitForNavigation',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['response' => [
                'responseId' => 'response-2',
                'url' => 'https://example.com/complete.html',
                'status' => 201,
                'statusText' => 'Created',
                'headers' => [],
            ]]);

        $response = $frame->waitForNavigation(new WaitForNavigationOptions(url: '**/complete.html', waitUntil: 'commit'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(201, $response->status());
    }

    public function testDragAndDropSendsNativeFrameCommand(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'source' => '#source',
                'target' => '#target',
                'options' => ['force' => true],
                'action' => 'frame.dragAndDrop',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['success' => true]);

        $this->assertSame($frame, $frame->dragAndDrop('#source', '#target', ['force' => true]));
    }

    public function testAddScriptTagSendsNativeFrameCommand(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => ['content' => 'window.frameReady = true;'],
                'action' => 'frame.addScriptTag',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['success' => true]);

        $this->assertSame($frame, $frame->addScriptTag(['content' => 'window.frameReady = true;']));
    }

    public function testAddStyleTagSendsNativeFrameCommand(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => ['content' => 'body { color: red; }'],
                'action' => 'frame.addStyleTag',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['success' => true]);

        $this->assertSame($frame, $frame->addStyleTag(['content' => 'body { color: red; }']));
    }

    public function testTitleSendsCommandAndReturnsString(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'frame.title',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['value' => 'Authentication']);

        $this->assertSame('Authentication', $frame->title());
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

    public function testGetByText(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $locator = $frame->getByText('Hello');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('text="Hello"', $locator->getSelector());
    }

    public function testGetByRole(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $locator = $frame->getByRole('button');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('internal:role=button', $locator->getSelector());
    }

    public function testGetByPlaceholder(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $locator = $frame->getByPlaceholder('Username');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('[placeholder="Username"]', $locator->getSelector());
    }

    public function testGetByTestId(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $locator = $frame->getByTestId('submit-btn');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('[data-testid="submit-btn"]', $locator->getSelector());
    }

    public function testGetByAltText(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $locator = $frame->getByAltText('Logo');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('[alt="Logo"]', $locator->getSelector());
    }

    public function testGetByTitle(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $locator = $frame->getByTitle('Tooltip');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('[title="Tooltip"]', $locator->getSelector());
    }

    public function testGetByLabel(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $locator = $frame->getByLabel('Email');
        $this->assertInstanceOf(LocatorInterface::class, $locator);
        $this->assertSame('label:text-is("Email") >> nth=0', $locator->getSelector());
    }

    public function testContentRejectsANonStringPayload(): void
    {
        $this->transport->method('send')->willReturn(['content' => 42]);
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid frame.content response');

        $frame->content();
    }

    public function testTitleRejectsANonStringPayload(): void
    {
        $this->transport->method('send')->willReturn(['value' => ['not', 'a', 'string']]);
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid frame.title response');

        $frame->title();
    }

    public function testGotoWithoutAResponsePayloadYieldsNull(): void
    {
        $this->transport->method('send')->willReturn([]);
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->assertNull($frame->goto('https://example.com'));
    }

    public function testGotoRejectsANonArrayResponsePayload(): void
    {
        $this->transport->method('send')->willReturn(['response' => 'not an array']);
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid frame response data from transport');

        $frame->goto('https://example.com');
    }

    public function testGotoRejectsAResponsePayloadWithNonStringKeys(): void
    {
        $this->transport->method('send')->willReturn(['response' => [0 => 'value']]);
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('non-string key');

        $frame->goto('https://example.com');
    }

    public function testEvaluateHandleNormalizesTheExpressionAndReturnsAHandle(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'expression' => '(arg) => { return document.body; }',
                'arg' => null,
                'action' => 'frame.evaluateHandle',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['handleId' => 'handle-1']);

        $this->assertInstanceOf(JSHandleInterface::class, $frame->evaluateHandle('return document.body;'));
    }

    public function testEvaluateHandleRejectsAResponseWithoutAHandleId(): void
    {
        $this->transport->method('send')->willReturn([]);
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid frame.evaluateHandle response');

        $frame->evaluateHandle('() => document.body');
    }

    public function testFrameElementReturnsAHandleToTheEmbeddingElement(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'frame.frameElement',
                'pageId' => $this->pageId,
                'frameSelector' => 'iframe#auth',
            ])
            ->willReturn(['handleId' => 'handle-2']);

        $this->assertInstanceOf(JSHandleInterface::class, $frame->frameElement());
    }

    public function testFrameElementRejectsAResponseWithoutAHandleId(): void
    {
        $this->transport->method('send')->willReturn(['handleId' => null]);
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid frame.frameElement response');

        $frame->frameElement();
    }

    public function testPageReturnsTheOwningPage(): void
    {
        $page = $this->createMock(PageInterface::class);
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger, $page);

        $this->assertSame($page, $frame->page());
    }

    public function testPageRejectsAFrameBuiltWithoutAPage(): void
    {
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This frame was not created from a page.');

        $frame->page();
    }

    public function testDescendantsCarryTheFramePageAlong(): void
    {
        $page = $this->createMock(PageInterface::class);
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger, $page);

        $this->assertSame($page, $frame->locator('button')->page());
        $this->assertSame($page, $frame->getByRole('button')->page());
        $this->assertSame($page, $frame->owner()->page());
        $this->assertSame($page, $frame->frameLocator('iframe#nested')->locator('button')->page());
    }

    public function testRelatedFramesCarryThePageAlong(): void
    {
        $page = $this->createMock(PageInterface::class);
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger, $page);
        $this->transport->method('send')->willReturnOnConsecutiveCalls(
            ['selector' => ':root'],
            ['frames' => [['selector' => 'iframe#auth >> iframe#child']]],
        );

        $parent = $frame->parentFrame();
        $this->assertInstanceOf(FrameInterface::class, $parent);
        $this->assertSame($page, $parent->page());
        $this->assertSame($page, $frame->childFrames()[0]->page());
    }

    public function testEvaluateLeavesABareExpressionUntouched(): void
    {
        $sent = [];
        $this->transport->method('send')->willReturnCallback(function (array $payload) use (&$sent): array {
            $sent[] = $payload;

            return ['result' => 'Example'];
        });
        $frame = new Frame($this->transport, $this->pageId, 'iframe#auth', $this->logger);

        $frame->evaluate('document.title');

        $this->assertSame('document.title', $sent[0]['expression']);
    }
}
