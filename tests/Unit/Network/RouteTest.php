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

namespace Playwright\Tests\Unit\Network;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Playwright\Network\RequestInterface;
use Playwright\Network\Response;
use Playwright\Network\Route;
use Playwright\Transport\TransportInterface;

#[CoversClass(Route::class)]
final class RouteTest extends TestCase
{
    private MockObject&TransportInterface $transport;

    private array $requestData;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->requestData = [
            'url' => 'https://example.com',
            'method' => 'GET',
            'headers' => ['Accept' => 'application/json'],
            'postData' => null,
            'resourceType' => 'fetch',
        ];
    }

    public function testRequest(): void
    {
        $route = $this->createRoute();
        $request = $route->request();

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertSame('https://example.com', $request->url());
    }

    public function testAbortWithDefaultErrorCode(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('sendAsync')
            ->with([
                'action' => 'route.abort',
                'routeId' => 'route456',
                'errorCode' => 'failed',
            ]);

        $route = $this->createRoute();
        $route->abort();
    }

    public function testAbortWithCustomErrorCode(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('sendAsync')
            ->with([
                'action' => 'route.abort',
                'routeId' => 'route456',
                'errorCode' => 'accessdenied',
            ]);

        $route = $this->createRoute();
        $route->abort('accessdenied');
    }

    public function testContinue(): void
    {
        $options = ['url' => 'https://example.com/new'];
        $this->transport
            ->expects($this->once())
            ->method('sendAsync')
            ->with([
                'action' => 'route.continue',
                'routeId' => 'route456',
                'options' => $options,
            ]);

        $route = $this->createRoute();
        $route->continue($options);
    }

    public function testFulfill(): void
    {
        $options = ['status' => 200, 'body' => 'foobar'];
        $this->transport
            ->expects($this->once())
            ->method('sendAsync')
            ->with([
                'action' => 'route.fulfill',
                'routeId' => 'route456',
                'options' => $options,
            ]);

        $route = $this->createRoute();
        $route->fulfill($options);
    }

    private function createRoute(): Route
    {
        return new Route(
            $this->transport,
            'route456',
            $this->requestData
        );
    }
}
