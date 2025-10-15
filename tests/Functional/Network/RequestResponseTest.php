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

namespace Playwright\Tests\Functional\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Network\Request;
use Playwright\Network\Response;
use Playwright\Network\ResponseInterface;
use Playwright\Page\Page;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(Page::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
final class RequestResponseTest extends FunctionalTestCase
{
    public function testCanWaitForResponse(): void
    {
        $this->goto('/network.html');

        $this->page->route('**/api/data.json', function ($route) {
            $route->fulfill([
                'status' => 200,
                'contentType' => 'application/json',
                'body' => \json_encode(['test' => 'data']),
            ]);
        });

        $response = $this->page->waitForResponse('**/api/data.json', [
            'action' => 'document.getElementById("fetch-json").click()',
        ]);

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(200, $response->status());
    }

    public function testCanGetResponseStatus(): void
    {
        $this->goto('/network.html');

        $this->page->route('**/api/data.json', function ($route) {
            $route->fulfill([
                'status' => 201,
                'contentType' => 'application/json',
                'body' => \json_encode(['created' => true]),
            ]);
        });

        $response = $this->page->waitForResponse('**/api/data.json', [
            'action' => 'document.getElementById("fetch-json").click()',
        ]);

        self::assertSame(201, $response->status());
    }

    public function testCanCheckResponseHeaders(): void
    {
        $this->goto('/network.html');

        $this->page->route('**/api/data.json', function ($route) {
            $route->fulfill([
                'status' => 200,
                'contentType' => 'application/json',
                'headers' => [
                    'X-Custom-Header' => 'test-value',
                ],
                'body' => \json_encode(['data' => 'test']),
            ]);
        });

        $response = $this->page->waitForResponse('**/api/data.json', [
            'action' => 'document.getElementById("fetch-json").click()',
        ]);

        $headers = $response->headers();
        self::assertIsArray($headers);
        self::assertArrayHasKey('content-type', $headers);
        self::assertStringContainsString('json', $headers['content-type']);
    }
}
