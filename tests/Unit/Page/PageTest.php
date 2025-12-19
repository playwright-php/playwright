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
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Input\KeyboardInterface;
use Playwright\Input\MouseInterface;
use Playwright\Locator\Locator;
use Playwright\Locator\Options\GetByRoleOptions;
use Playwright\Locator\Options\LocatorOptions;
use Playwright\Page\Page;
use Playwright\Page\PageEventHandlerInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(Page::class)]
class PageTest extends TestCase
{
    protected Page $page;
    protected \PHPUnit\Framework\MockObject\MockObject $transport;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $context = $this->createMock(BrowserContextInterface::class);
        $pageId = 'page-id';

        $this->page = new Page($this->transport, $context, $pageId);
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
        $result = $locator->getOptions();

        $this->assertTrue($result['checked']);
        $this->assertSame('Loading', $result['hasNotText']);
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
}
