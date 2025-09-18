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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Playwright\Dialog\Dialog;
use Playwright\Network\Request;
use Playwright\Network\Response;
use Playwright\Page\PageEventHandler;
use Playwright\Page\PageInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(PageEventHandler::class)]
final class PageEventHandlerTest extends TestCase
{
    private MockObject&TransportInterface $transport;

    private MockObject&PageInterface $page;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->page = $this->createMock(PageInterface::class);
    }

    public function testPublicEmitDispatchesEventToListener(): void
    {
        $handler = new PageEventHandler();
        $wasCalled = false;
        $receivedArgs = null;

        $handler->on('foo', function (...$args) use (&$wasCalled, &$receivedArgs) {
            $wasCalled = true;
            $receivedArgs = $args;
        });

        $handler->publicEmit('foo', ['bar', 'baz']);

        $this->assertTrue($wasCalled);
        $this->assertSame(['bar', 'baz'], $receivedArgs);
    }

    public function testOnDialogRegistersDialogHandler(): void
    {
        $handler = new PageEventHandler();
        $wasCalled = false;
        $receivedDialog = null;

        $dialog = new Dialog($this->page, 'dialog1', 'alert', 'message', null);

        $handler->onDialog(function (Dialog $d) use (&$wasCalled, &$receivedDialog) {
            $wasCalled = true;
            $receivedDialog = $d;
        });

        $handler->publicEmit('dialog', [$dialog]);

        $this->assertTrue($wasCalled);
        $this->assertSame($dialog, $receivedDialog);
    }

    public function testOnConsoleRegistersConsoleHandler(): void
    {
        $handler = new PageEventHandler();
        $wasCalled = false;
        $receivedMessage = null;

        $consoleMessage = new \stdClass();

        $handler->onConsole(function ($message) use (&$wasCalled, &$receivedMessage) {
            $wasCalled = true;
            $receivedMessage = $message;
        });

        $handler->publicEmit('console', [$consoleMessage]);

        $this->assertTrue($wasCalled);
        $this->assertSame($consoleMessage, $receivedMessage);
    }

    public function testOnRequestRegistersRequestHandler(): void
    {
        $handler = new PageEventHandler();
        $wasCalled = false;
        $receivedRequest = null;

        $request = new Request(['url' => 'https://example.com', 'method' => 'GET', 'headers' => [], 'resourceType' => 'document']);

        $handler->onRequest(function (Request $r) use (&$wasCalled, &$receivedRequest) {
            $wasCalled = true;
            $receivedRequest = $r;
        });

        $handler->publicEmit('request', [$request]);

        $this->assertTrue($wasCalled);
        $this->assertSame($request, $receivedRequest);
    }

    public function testOnResponseRegistersResponseHandler(): void
    {
        $handler = new PageEventHandler();
        $wasCalled = false;
        $receivedResponse = null;

        $response = new Response($this->transport, 'page1', ['url' => 'https://example.com', 'status' => 200, 'statusText' => 'OK', 'headers' => [], 'responseId' => 'res1']);

        $handler->onResponse(function (Response $r) use (&$wasCalled, &$receivedResponse) {
            $wasCalled = true;
            $receivedResponse = $r;
        });

        $handler->publicEmit('response', [$response]);

        $this->assertTrue($wasCalled);
        $this->assertSame($response, $receivedResponse);
    }
}
