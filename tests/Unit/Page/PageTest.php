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

namespace Playwright\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Playwright\API\APIRequestContextInterface;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Clock\ClockInterface;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Console\ConsoleMessage;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Exception\RuntimeException;
use Playwright\Exception\TimeoutException;
use Playwright\Frame\FrameInterface;
use Playwright\Input\KeyboardInterface;
use Playwright\Input\MouseInterface;
use Playwright\Input\TouchscreenInterface;
use Playwright\JSHandle\JSHandleInterface;
use Playwright\Locator\Locator;
use Playwright\Locator\Options\AriaSnapshotOptions;
use Playwright\Locator\Options\GetByRoleOptions;
use Playwright\Locator\Options\LocatorOptions;
use Playwright\Page\Options\DragAndDropOptions;
use Playwright\Page\Options\EmulateMediaOptions;
use Playwright\Page\Options\WaitForRequestOptions;
use Playwright\Page\Page;
use Playwright\Page\PageEventHandlerInterface;
use Playwright\Regex;
use Playwright\Screencast\ScreencastInterface;
use Playwright\Transport\TransportInterface;
use Playwright\Video\VideoInterface;
use Playwright\WebStorage\WebStorageInterface;

#[CoversClass(Page::class)]
class PageTest extends TestCase
{
    protected Page $page;
    protected MockObject&TransportInterface $transport;
    protected MockObject&BrowserContextInterface $context;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->context = $this->createMock(BrowserContextInterface::class);

        $this->page = new Page($this->transport, $this->context, 'page-id', new PlaywrightConfig());
    }

    public function testGetKeyboard(): void
    {
        $keyboard = $this->page->keyboard();

        $this->assertInstanceOf(KeyboardInterface::class, $keyboard);
    }

    public function testGetMouse(): void
    {
        $mouse = $this->page->mouse();

        $this->assertInstanceOf(MouseInterface::class, $mouse);
    }

    public function testGetTouchscreen(): void
    {
        $touchscreen = $this->page->touchscreen();

        $this->assertInstanceOf(TouchscreenInterface::class, $touchscreen);
    }

    public function testGetLocalStorage(): void
    {
        $this->assertInstanceOf(WebStorageInterface::class, $this->page->localStorage());
        $this->assertSame($this->page->localStorage, $this->page->localStorage());
    }

    public function testGetSessionStorage(): void
    {
        $this->assertInstanceOf(WebStorageInterface::class, $this->page->sessionStorage());
        $this->assertSame($this->page->sessionStorage, $this->page->sessionStorage());
    }

    public function testLocalAndSessionStorageAreDistinctInstances(): void
    {
        $this->assertNotSame($this->page->localStorage, $this->page->sessionStorage);
    }

    public function testGetScreencast(): void
    {
        $this->assertInstanceOf(ScreencastInterface::class, $this->page->screencast());
        $this->assertSame($this->page->screencast, $this->page->screencast());
    }

    public function testGetEvents(): void
    {
        $events = $this->page->events();

        $this->assertInstanceOf(PageEventHandlerInterface::class, $events);
    }

    public function testLocatorAcceptsLocatorOptions(): void
    {
        $options = new LocatorOptions(hasText: 'Save', strict: true);

        $locator = $this->page->locator('button.save', $options);

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame([
            'hasText' => 'Save',
            'strict' => true,
        ], $locator->getOptions());
    }

    public function testGetByRoleAcceptsGetByRoleOptions(): void
    {
        $options = new GetByRoleOptions(
            checked: true,
            locatorOptions: new LocatorOptions(hasNotText: 'Loading')
        );

        $locator = $this->page->getByRole('button', $options);

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:role=button[checked]', $locator->getSelector());
        $this->assertSame('Loading', $locator->getOptions()['hasNotText']);
    }

    public function testGetByTextBuildsCaseInsensitiveSelector(): void
    {
        $locator = $this->page->getByText('Hello World');

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:text=/Hello World/i', $locator->getSelector());
    }

    public function testGetByTextBuildsExactSelector(): void
    {
        $locator = $this->page->getByText('Hello World', ['exact' => true]);

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:text="Hello World"', $locator->getSelector());
    }

    public function testGetByPlaceholderBuildsCaseInsensitiveSelector(): void
    {
        $locator = $this->page->getByPlaceholder('Username');

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:attr=[placeholder=/Username/i]', $locator->getSelector());
    }

    public function testGetByPlaceholderBuildsExactSelector(): void
    {
        $locator = $this->page->getByPlaceholder('Username', ['exact' => true]);

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:attr=[placeholder="Username"]', $locator->getSelector());
    }

    public function testGetByTitleBuildsCaseInsensitiveSelector(): void
    {
        $locator = $this->page->getByTitle('Page Title');

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:attr=[title=/Page Title/i]', $locator->getSelector());
    }

    public function testGetByTitleBuildsExactSelector(): void
    {
        $locator = $this->page->getByTitle('Page Title', ['exact' => true]);

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:attr=[title="Page Title"]', $locator->getSelector());
    }

    public function testGetByAltTextBuildsCaseInsensitiveSelector(): void
    {
        $locator = $this->page->getByAltText('Logo');

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:attr=[alt=/Logo/i]', $locator->getSelector());
    }

    public function testGetByAltTextBuildsExactSelector(): void
    {
        $locator = $this->page->getByAltText('Logo', ['exact' => true]);

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:attr=[alt="Logo"]', $locator->getSelector());
    }

    public function testGetByLabelBuildsCaseInsensitiveSelector(): void
    {
        $locator = $this->page->getByLabel('Password');

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:label=/Password/i', $locator->getSelector());
    }

    public function testGetByLabelBuildsExactSelector(): void
    {
        $locator = $this->page->getByLabel('Password', ['exact' => true]);

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:label="Password"', $locator->getSelector());
    }

    public function testGetByTextWithRegex(): void
    {
        $locator = $this->page->getByText(new Regex('/hello/i'));

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:text=/hello/i', $locator->getSelector());
    }

    public function testGetByTextWithRegexIgnoresExact(): void
    {
        $locator = $this->page->getByText(new Regex('/hello/'), ['exact' => true]);

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:text=/hello/', $locator->getSelector());
    }

    public function testGetByPlaceholderWithRegex(): void
    {
        $locator = $this->page->getByPlaceholder(new Regex('/user/i'));

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:attr=[placeholder=/user/i]', $locator->getSelector());
    }

    public function testGetByTitleWithRegex(): void
    {
        $locator = $this->page->getByTitle(new Regex('/title/i'));

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:attr=[title=/title/i]', $locator->getSelector());
    }

    public function testGetByAltTextWithRegex(): void
    {
        $locator = $this->page->getByAltText(new Regex('/logo/i'));

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:attr=[alt=/logo/i]', $locator->getSelector());
    }

    public function testGetByLabelWithRegex(): void
    {
        $locator = $this->page->getByLabel(new Regex('/pass/i'));

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:label=/pass/i', $locator->getSelector());
    }

    public function testGetByRoleWithRegexName(): void
    {
        $locator = $this->page->getByRole('button', ['name' => new Regex('/Submit/i')]);

        $this->assertInstanceOf(Locator::class, $locator);
        $this->assertSame('internal:role=button[name=/Submit/i]', $locator->getSelector());
    }

    public function testGotoSendsCommandAndReturnsResponse(): void
    {
        $url = 'https://example.com';
        $responseData = ['url' => $url, 'status' => 200, 'statusText' => 'OK', 'headers' => [], 'responseId' => 'res-1'];

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'url' => $url,
                'options' => [],
                'action' => 'page.goto',
                'pageId' => 'page-id',
            ])
            ->willReturn(['response' => $responseData]);

        $response = $this->page->goto($url);

        $this->assertInstanceOf(\Playwright\Network\ResponseInterface::class, $response);
        $this->assertSame(200, $response->status());
    }

    public function testClickSendsCommand(): void
    {
        $selector = 'button';
        $options = ['force' => true];

        $this->transport->expects($this->exactly(3))
            ->method('send')
            ->willReturnCallback(function (array $payload) {
                if ('locator.isVisible' === $payload['action']) {
                    return ['value' => true];
                }
                if ('locator.isEnabled' === $payload['action']) {
                    return ['value' => true];
                }
                if ('locator.click' === $payload['action']) {
                    $this->assertSame(['force' => true], $payload['options']);

                    return [];
                }
                $this->fail('Unexpected action: '.$payload['action']);
            });

        $this->page->click($selector, $options);
    }

    public function testTypeSendsCommand(): void
    {
        $selector = 'input';
        $text = 'hello';
        $options = ['delay' => 100.0];

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'text' => $text,
                'options' => ['delay' => 100.0],
                'action' => 'locator.type',
                'pageId' => 'page-id',
                'selector' => $selector,
            ])
            ->willReturn([]);

        $this->page->type($selector, $text, $options);
    }

    public function testScreenshotSendsCommandAndReturnsPath(): void
    {
        $path = 'screenshot.png';
        $options = ['fullPage' => true];

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => ['fullPage' => true, 'path' => $path],
                'action' => 'page.screenshot',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $result = $this->page->screenshot($path, $options);

        $this->assertSame($path, $result);
    }

    public function testTitleSendsCommandAndReturnsString(): void
    {
        $title = 'Page Title';

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'page.title',
                'pageId' => 'page-id',
            ])
            ->willReturn(['value' => $title]);

        $result = $this->page->title();

        $this->assertSame($title, $result);
    }

    public function testUrlSendsCommandAndReturnsString(): void
    {
        $url = 'https://example.com';

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'page.url',
                'pageId' => 'page-id',
            ])
            ->willReturn(['value' => $url]);

        $result = $this->page->url();

        $this->assertSame($url, $result);
    }

    public function testContentSendsCommandAndReturnsString(): void
    {
        $content = '<html></html>';

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'page.content',
                'pageId' => 'page-id',
            ])
            ->willReturn(['content' => $content]);

        $result = $this->page->content();

        $this->assertSame($content, $result);
    }

    public function testSetContentSendsCommand(): void
    {
        $html = '<html></html>';
        $options = ['timeout' => 1000.0];

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'html' => $html,
                'options' => ['timeout' => 1000.0],
                'action' => 'page.setContent',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $this->page->setContent($html, $options);
    }

    public function testEvaluateSendsCommandAndReturnsResult(): void
    {
        $expression = '1 + 1';
        $result = 2;

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'expression' => $expression,
                'arg' => null,
                'action' => 'page.evaluate',
                'pageId' => 'page-id',
            ])
            ->willReturn(['result' => $result]);

        $this->assertSame($result, $this->page->evaluate($expression));
    }

    public function testWaitForSelectorSendsCommandAndReturnsLocator(): void
    {
        $selector = 'div';
        $options = ['state' => 'visible'];

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'selector' => $selector,
                'options' => ['state' => 'visible'],
                'action' => 'page.waitForSelector',
                'pageId' => 'page-id',
            ])
            ->willReturn(['element' => ['guid' => 'element-guid']]);

        $locator = $this->page->waitForSelector($selector, $options);
        $this->assertInstanceOf(\Playwright\Locator\LocatorInterface::class, $locator);
    }

    public function testWaitForLoadStateSendsCommand(): void
    {
        $state = 'networkidle';
        $options = ['timeout' => 5000.0];

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'state' => $state,
                'options' => ['timeout' => 5000.0],
                'action' => 'page.waitForLoadState',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $this->page->waitForLoadState($state, $options);
    }

    public function testWaitForURLSendsCommand(): void
    {
        $url = 'https://example.com';
        $options = ['timeout' => 5000.0];

        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'url' => $url,
                'options' => ['timeout' => 5000.0],
                'action' => 'page.waitForURL',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $this->page->waitForURL($url, $options);
    }

    public function testGoBackSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => [],
                'action' => 'page.goBack',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $result = $this->page->goBack();
        $this->assertSame($this->page, $result);
    }

    public function testGoForwardSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => [],
                'action' => 'page.goForward',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $result = $this->page->goForward();
        $this->assertSame($this->page, $result);
    }

    public function testReloadSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => [],
                'action' => 'page.reload',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $result = $this->page->reload();
        $this->assertSame($this->page, $result);
    }

    public function testSetDefaultTimeoutSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'timeout' => 500,
                'action' => 'page.setDefaultTimeout',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $result = $this->page->setDefaultTimeout(500);
        $this->assertSame($this->page, $result);
    }

    public function testAddInitScriptSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'script' => 'window.pageInit = true;',
                'action' => 'page.addInitScript',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $result = $this->page->addInitScript('window.pageInit = true;');

        $this->assertSame($this->page, $result);
    }

    public function testSetDefaultNavigationTimeoutSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'timeout' => 10000,
                'action' => 'page.setDefaultNavigationTimeout',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $result = $this->page->setDefaultNavigationTimeout(10000);
        $this->assertSame($this->page, $result);
    }

    public function testDragAndDropSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'source' => '#source',
                'target' => '#target',
                'options' => [
                    'force' => true,
                    'strict' => true,
                ],
                'action' => 'page.dragAndDrop',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $result = $this->page->dragAndDrop('#source', '#target', new DragAndDropOptions(force: true, strict: true));

        $this->assertSame($this->page, $result);
    }

    public function testSetExtraHTTPHeadersSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'headers' => ['X-Test' => 'page'],
                'action' => 'page.setExtraHTTPHeaders',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $result = $this->page->setExtraHTTPHeaders(['X-Test' => 'page']);

        $this->assertSame($this->page, $result);
    }

    public function testSetExtraHTTPHeadersRejectsNonStringEntries(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->page->setExtraHTTPHeaders(['X-Test' => 1]);
    }

    public function testUnrouteSendsPageCommand(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);
        $page = new Page($transport, $context, 'page-id', new PlaywrightConfig());

        $context->expects($this->never())
            ->method('unroute');
        $transport->expects($this->once())
            ->method('send')
            ->with([
                'url' => '**/api/**',
                'action' => 'page.unroute',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $page->unroute('**/api/**');
    }

    public function testMainFrame(): void
    {
        $page = $this->createPage();
        $frame = $page->mainFrame();
        $this->assertSame('Frame(selector=":root")', (string) $frame);
    }

    public function testFrames(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(fn (array $payload) => 'page.frames' === $payload['action'] && 'page-1' === $payload['pageId']))
            ->willReturn(['frames' => [
                ['selector' => 'iframe#one'],
                ['selector' => 'iframe[name="two"]'],
            ]]);

        $page = $this->createPage();
        $frames = $page->frames();
        $this->assertCount(2, $frames);
        $this->assertSame('Frame(selector="iframe#one")', (string) $frames[0]);
    }

    public function testFrameFind(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) {
                return 'page.frame' === $payload['action']
                    && 'page-1' === $payload['pageId']
                    && isset($payload['options']['name'])
                    && 'foo' === $payload['options']['name'];
            }))
            ->willReturn(['selector' => 'iframe#foo']);

        $page = $this->createPage();
        $frame = $page->frame(['name' => 'foo']);
        $this->assertNotNull($frame);
        $this->assertSame('Frame(selector="iframe#foo")', (string) $frame);
    }

    public function testNormalizesReturnBodyToFunction(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'page.evaluate' === $payload['action']
                    && '(arg) => { return 42; }' === $payload['expression'];
            }))
            ->willReturn(['result' => 42]);

        $page = new Page($transport, $context, 'p1', new PlaywrightConfig());
        $result = $page->evaluate('return 42;');
        $this->assertSame(42, $result);
    }

    public function testLeavesPlainExpressionUntouched(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'page.evaluate' === $payload['action']
                    && 'document.title' === $payload['expression'];
            }))
            ->willReturn(['result' => 'Hello']);

        $page = new Page($transport, $context, 'p1', new PlaywrightConfig());
        $result = $page->evaluate('document.title');
        $this->assertSame('Hello', $result);
    }

    #[Test]
    public function itSendsPauseCommand(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) {
                return ($payload['action'] ?? null) === 'page.pause'
                    && ($payload['pageId'] ?? null) === 'page_1';
            }))
            ->willReturn(['success' => true]);

        $page = new Page($transport, $context, 'page_1', new PlaywrightConfig());
        $page->pause();
        $this->assertTrue(true, 'pause() should dispatch page.pause');
    }

    public function testPdfUsesProvidedPath(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $expectedPath = sys_get_temp_dir().'/playwright-pdf-unit-test.pdf';

        $transport->expects($this->once())
            ->method('send')
            ->with($this->callback(function (array $payload) use ($expectedPath) {
                $this->assertSame('page.pdf', $payload['action']);
                $this->assertSame('page-unit', $payload['pageId']);
                $this->assertSame($expectedPath, $payload['options']['path'] ?? null);

                return true;
            }))
            ->willReturn([]);

        $page = new Page($transport, $context, 'page-unit', new PlaywrightConfig());

        $result = $page->pdf($expectedPath);

        $this->assertSame($expectedPath, $result);
    }

    public function testPdfContentReturnsBinaryAndCleansUpTempFile(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $pdfDir = sys_get_temp_dir().'/playwright-pdf-content-'.uniqid('', true);
        mkdir($pdfDir, 0755, true);

        $config = new PlaywrightConfig(screenshotDir: $pdfDir);
        $pdfBytes = '%PDF-1.4 mock';

        $transport->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (array $payload) use ($pdfBytes): array {
                $this->assertSame('page.pdf', $payload['action']);
                $path = $payload['options']['path'] ?? null;
                $this->assertIsString($path);
                file_put_contents($path, $pdfBytes);

                return [];
            });

        $page = new Page($transport, $context, 'page-unit', $config);

        $content = $page->pdfContent();

        $this->assertSame($pdfBytes, $content);
        $this->assertDirectoryHasNoFiles($pdfDir);

        rmdir($pdfDir);
    }

    public function testPdfContentRejectsPathOption(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);

        $page = new Page($transport, $context, 'page-unit', new PlaywrightConfig());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Do not provide a "path" option when requesting inline PDF content.');

        $page->pdfContent(['path' => '/tmp/should-not-be-used.pdf']);
    }

    public function testWaitForPopupSuccess(): void
    {
        $page = $this->createPage('page1');

        $actionExecuted = false;
        $action = function () use (&$actionExecuted) {
            $actionExecuted = true;
        };

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) use (&$actionExecuted) {
                // Action should be executed before transport call
                $this->assertTrue($actionExecuted);

                return 'page.waitForPopup' === $payload['action']
                    && 'page1' === $payload['pageId']
                    && 30000 === $payload['timeout'];
            }))
            ->willReturn(['popupPageId' => 'popup123']);

        $popup = $page->waitForPopup($action);

        $this->assertInstanceOf(Page::class, $popup);
        $this->assertTrue($actionExecuted);
    }

    public function testWaitForPopupWithCustomTimeout(): void
    {
        $page = $this->createPage('page1');

        $actionExecuted = false;
        $action = function () use (&$actionExecuted) {
            $actionExecuted = true;
        };

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) use (&$actionExecuted) {
                $this->assertTrue($actionExecuted);

                return 'page.waitForPopup' === $payload['action']
                    && 5000.0 === (float) $payload['timeout'];
            }))
            ->willReturn(['popupPageId' => 'popup456']);

        $popup = $page->waitForPopup($action, ['timeout' => 5000]);

        $this->assertInstanceOf(Page::class, $popup);
    }

    public function testWaitForPopupTimeout(): void
    {
        $page = $this->createPage('page1');

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn([]);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('No popup was created within the timeout period');

        $action = function () {};
        $page->waitForPopup($action);
    }

    public function testWaitForPopupInvalidResponse(): void
    {
        $page = $this->createPage('page1');

        $this->transport
            ->expects($this->once())
            ->method('send')
            ->willReturn(['popupPageId' => null]);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('No popup was created within the timeout period');

        $action = function () {};
        $page->waitForPopup($action);
    }

    public function testRequestIsCached(): void
    {
        $transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);
        $api = $this->createMock(APIRequestContextInterface::class);

        $context->expects($this->once())
            ->method('request')
            ->willReturn($api);

        $page = new Page($transport, $context, 'page-1', new PlaywrightConfig());

        $first = $page->request();
        $second = $page->request();

        $this->assertSame($api, $first);
        $this->assertSame($first, $second, 'Page::request should return cached instance');
    }

    public function testConsoleMessagesSendsFilterAndHydratesSnapshots(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => ['filter' => 'all'],
                'action' => 'page.consoleMessages',
                'pageId' => 'page-id',
            ])
            ->willReturn(['messages' => [
                [
                    'type' => 'warning',
                    'text' => 'Be careful',
                    'args' => [],
                    'location' => ['url' => 'https://example.test/app.js'],
                    'timestamp' => 123.4,
                ],
                'not-a-message',
            ]]);

        $messages = $this->page->consoleMessages(['filter' => 'all']);

        $this->assertCount(1, $messages);
        $this->assertInstanceOf(ConsoleMessage::class, $messages[0]);
        $this->assertSame('warning', $messages[0]->type());
        $this->assertSame('Be careful', $messages[0]->text());
        $this->assertSame($this->page, $messages[0]->page());
    }

    public function testConsoleMessagesRejectsANonListResponse(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->willReturn(['messages' => 'nope']);

        $this->expectException(ProtocolErrorException::class);

        $this->page->consoleMessages();
    }

    public function testConsoleMessagesRejectsNonStringKeys(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->willReturn(['messages' => [[0 => 'log']]]);

        $this->expectException(ProtocolErrorException::class);

        $this->page->consoleMessages();
    }

    public function testClearConsoleMessagesSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'page.clearConsoleMessages',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $this->assertSame($this->page, $this->page->clearConsoleMessages());
    }

    public function testClearPageErrorsSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'page.clearPageErrors',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $this->assertSame($this->page, $this->page->clearPageErrors());
    }

    public function testRequestsHydratesRequestSnapshots(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'page.requests',
                'pageId' => 'page-id',
            ])
            ->willReturn(['requests' => [
                [
                    'url' => 'https://example.test/resource.js',
                    'method' => 'GET',
                    'headers' => [],
                    'postData' => null,
                    'resourceType' => 'script',
                ],
                'not-a-request',
            ]]);

        $requests = $this->page->requests();

        $this->assertCount(1, $requests);
        $this->assertSame('https://example.test/resource.js', $requests[0]->url());
        $this->assertSame('script', $requests[0]->resourceType());
    }

    public function testRequestsRejectsANonListResponse(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->willReturn(['requests' => 'nope']);

        $this->expectException(ProtocolErrorException::class);

        $this->page->requests();
    }

    public function testWaitForRequestSendsTheGlobAndJavaScriptAction(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'url' => '**/page2.html',
                'options' => ['timeout' => 500.0],
                'jsAction' => "document.querySelector('a').click()",
                'action' => 'page.waitForRequest',
                'pageId' => 'page-id',
            ])
            ->willReturn(['request' => [
                'url' => 'https://example.test/page2.html',
                'method' => 'GET',
                'headers' => [],
                'resourceType' => 'document',
            ]]);

        $request = $this->page->waitForRequest('**/page2.html', [
            'timeout' => 500.0,
            'action' => "document.querySelector('a').click()",
        ]);

        $this->assertSame('https://example.test/page2.html', $request->url());
    }

    public function testWaitForRequestAcceptsAnOptionsObject(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'url' => '**/*.css',
                'options' => ['timeout' => 20.0],
                'jsAction' => null,
                'action' => 'page.waitForRequest',
                'pageId' => 'page-id',
            ])
            ->willReturn(['request' => [
                'url' => 'https://example.test/asset.css',
                'method' => 'GET',
                'headers' => [],
                'resourceType' => 'stylesheet',
            ]]);

        $request = $this->page->waitForRequest('**/*.css', new WaitForRequestOptions(20.0));

        $this->assertSame('stylesheet', $request->resourceType());
    }

    public function testEmulateMediaSendsOptionsAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => ['colorScheme' => 'dark', 'media' => 'print'],
                'action' => 'page.emulateMedia',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $this->assertSame($this->page, $this->page->emulateMedia(['media' => 'print', 'colorScheme' => 'dark']));
    }

    public function testEmulateMediaAcceptsAnOptionsObject(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => ['reducedMotion' => 'reduce'],
                'action' => 'page.emulateMedia',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $this->page->emulateMedia(new EmulateMediaOptions(reducedMotion: 'reduce'));
    }

    public function testRequestGCSendsCommandAndReturnsSelf(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'page.requestGC',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $this->assertSame($this->page, $this->page->requestGC());
    }

    public function testOpenerHydratesAPageFromTheTransportId(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'action' => 'page.opener',
                'pageId' => 'page-id',
            ])
            ->willReturn(['pageId' => 'page-opener']);

        $opener = $this->page->opener();

        $this->assertInstanceOf(Page::class, $opener);
        $this->assertSame('page-opener', $opener->getPageIdForTransport());
    }

    public function testOpenerReturnsNullWithoutAnOpenerPage(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->willReturn(['pageId' => null]);

        $this->assertNull($this->page->opener());
    }

    public function testOpenerRejectsANonStringPageId(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->willReturn(['pageId' => 42]);

        $this->expectException(ProtocolErrorException::class);

        $this->page->opener();
    }

    public function testUnrouteAllSendsItsBehavior(): void
    {
        $this->transport->expects($this->once())
            ->method('send')
            ->with([
                'options' => ['behavior' => 'wait'],
                'action' => 'page.unrouteAll',
                'pageId' => 'page-id',
            ])
            ->willReturn([]);

        $this->page->unrouteAll(['behavior' => 'wait']);
    }

    public function testEvaluateHandleNormalizesTheExpressionAndReturnsAHandle(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'page.evaluateHandle' === $payload['action']
                    && '(arg) => { return document.body; }' === $payload['expression']
                    && null === $payload['arg'];
            }))
            ->willReturn(['handleId' => 'handle-1']);

        $this->assertInstanceOf(JSHandleInterface::class, $this->page->evaluateHandle('return document.body;'));
    }

    public function testEvaluateHandleRejectsAResponseWithoutAHandleId(): void
    {
        $this->transport->method('send')->willReturn([]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid page.evaluateHandle response');

        $this->page->evaluateHandle('() => document.body');
    }

    public function testLocatorRetainsItsOriginatingPage(): void
    {
        $this->assertSame($this->page, $this->page->locator('button.save')->page());
        $this->assertSame($this->page, $this->page->getByRole('button')->page());
        $this->assertSame($this->page, $this->page->frameLocator('iframe')->locator('button')->page());
    }

    public function testMainFrameRetainsItsOriginatingPage(): void
    {
        $this->assertSame($this->page, $this->page->mainFrame()->page());
    }

    public function testQueriedFramesRetainTheirOriginatingPage(): void
    {
        $this->transport->method('send')->willReturnOnConsecutiveCalls(
            ['frames' => [['selector' => 'iframe#one']]],
            ['selector' => 'iframe#one'],
        );

        $this->assertSame($this->page, $this->page->frames()[0]->page());

        $frame = $this->page->frame(['name' => 'one']);
        $this->assertInstanceOf(FrameInterface::class, $frame);
        $this->assertSame($this->page, $frame->page());
    }

    private function createPage(string $pageId = 'page-1'): Page
    {
        return new Page($this->transport, $this->context, $pageId, new PlaywrightConfig());
    }

    private function assertDirectoryHasNoFiles(string $directory): void
    {
        $files = array_diff(scandir($directory) ?: [], ['.', '..']);
        $this->assertEmpty($files, sprintf('Directory %s should be empty', $directory));
    }

    public function testAriaSnapshotRejectsANonStringResponse(): void
    {
        $this->transport->method('send')->willReturn([]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid ariaSnapshot response');

        $this->page->ariaSnapshot();
    }

    public function testAriaSnapshotSendsOptionsAndReturnsTheSnapshot(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (array $payload): bool {
                return 'page.ariaSnapshot' === $payload['action']
                    && ['timeout' => 500.0] === $payload['options'];
            }))
            ->willReturn(['value' => '- button "Save"']);

        $this->assertSame('- button "Save"', $this->page->ariaSnapshot(new AriaSnapshotOptions(timeout: 500.0)));
    }

    public function testClockIsTheContextClock(): void
    {
        $clock = $this->createMock(ClockInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);
        $context->method('clock')->willReturn($clock);

        $page = new Page($this->transport, $context, 'page-1', new PlaywrightConfig());

        $this->assertSame($clock, $page->clock());
    }

    public function testHideHighlightSendsCommand(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (array $payload): bool => 'page.hideHighlight' === $payload['action']))
            ->willReturn([]);

        $this->assertSame($this->page, $this->page->hideHighlight());
    }

    public function testVideoExposesTheRecordingPath(): void
    {
        $this->transport
            ->method('send')
            ->willReturn(['video' => ['videoId' => 'video_1', 'path' => '/tmp/videos/page.webm']]);

        $video = $this->page->video();

        $this->assertInstanceOf(VideoInterface::class, $video);
        $this->assertSame('/tmp/videos/page.webm', $video->path());
    }

    public function testVideoIsNullWhenTheContextDoesNotRecord(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static fn (array $payload): bool => 'page.video' === $payload['action']))
            ->willReturn(['video' => null]);

        $this->assertNull($this->page->video());
    }

    public function testVideoRejectsAMalformedResponse(): void
    {
        $this->transport->method('send')->willReturn(['video' => ['videoId' => 'video_1']]);

        $this->expectException(ProtocolErrorException::class);
        $this->expectExceptionMessage('Invalid video response');

        $this->page->video();
    }
}
