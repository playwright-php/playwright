<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Mocks;

/**
 * Mock Playwright server for testing without Node.js dependencies.
 * Simulates JSON-RPC communication and browser responses.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class MockPlaywrightServer
{
    /** @var array<string, mixed> */
    private array $responses = [];

    /** @var array<int, array{method: string, params: mixed}> */
    private array $requests = [];

    private int $nextId = 1;
    private bool $connected = false;

    public function __construct()
    {
        $this->setupDefaultResponses();
    }

    /**
     * Start the mock server (simulate connection).
     */
    public function start(): void
    {
        $this->connected = true;
    }

    /**
     * Stop the mock server.
     */
    public function stop(): void
    {
        $this->connected = false;
        $this->requests = [];
    }

    /**
     * Check if server is running.
     */
    public function isRunning(): bool
    {
        return $this->connected;
    }

    /**
     * Handle a JSON-RPC request and return mock response.
     *
     * @param array<string, mixed> $request
     *
     * @return array<string, mixed>
     */
    public function handleRequest(array $request): array
    {
        if (!$this->connected) {
            throw new \RuntimeException('Mock server not running');
        }

        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? $this->nextId++;

        $this->requests[] = ['method' => $method, 'params' => $params];

        if (isset($this->responses[$method])) {
            $response = $this->responses[$method];

            if (is_callable($response)) {
                $result = $response($params);
            } else {
                $result = $response;
            }

            return [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $result,
            ];
        }

        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => ['status' => 'ok', 'method' => $method],
        ];
    }

    /**
     * Set a custom response for a specific method.
     */
    public function setResponse(string $method, mixed $response): void
    {
        $this->responses[$method] = $response;
    }

    /**
     * Set an error response for a method.
     */
    public function setErrorResponse(string $method, string $errorMessage, int $errorCode = -1): void
    {
        $this->responses[$method] = function () use ($errorMessage, $errorCode) {
            throw new \RuntimeException("Mock error: $errorMessage", $errorCode);
        };
    }

    /**
     * Get all requests that were made.
     *
     * @return array<int, array{method: string, params: mixed}>
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * Get requests for a specific method.
     *
     * @return array<int, array{method: string, params: mixed}>
     */
    public function getRequestsFor(string $method): array
    {
        return array_filter($this->requests, fn ($req) => $req['method'] === $method);
    }

    /**
     * Clear all recorded requests.
     */
    public function clearRequests(): void
    {
        $this->requests = [];
    }

    /**
     * Setup default responses for common Playwright methods.
     */
    private function setupDefaultResponses(): void
    {
        $this->responses['Browser.newContext'] = [
            'contextId' => 'context_1',
        ];

        $this->responses['BrowserContext.newPage'] = [
            'pageId' => 'page_1',
        ];

        $this->responses['Browser.version'] = [
            'version' => '1.40.0',
        ];

        $this->responses['Page.goto'] = [
            'loaderId' => 'loader_1',
            'url' => 'https://example.com',
        ];

        $this->responses['Page.title'] = [
            'title' => 'Mock Page Title',
        ];

        $this->responses['Page.url'] = [
            'url' => 'https://example.com',
        ];

        $this->responses['Page.screenshot'] = [
            'screenshot' => base64_encode('mock_image_data'),
        ];

        $this->responses['Page.locator'] = [
            'locatorId' => 'locator_1',
        ];

        $this->responses['Locator.click'] = [
            'status' => 'clicked',
        ];

        $this->responses['Locator.textContent'] = [
            'text' => 'Mock text content',
        ];

        $this->responses['Locator.isVisible'] = [
            'visible' => true,
        ];

        $this->responses['Locator.count'] = [
            'count' => 1,
        ];

        $this->responses['Page.route'] = [
            'routeId' => 'route_1',
        ];

        $this->responses['Route.fulfill'] = [
            'status' => 'fulfilled',
        ];

        $this->responses['Page.keyboard'] = [
            'keyboardId' => 'keyboard_1',
        ];

        $this->responses['Keyboard.type'] = [
            'status' => 'typed',
        ];

        $this->responses['Page.mouse'] = [
            'mouseId' => 'mouse_1',
        ];

        $this->responses['Mouse.click'] = [
            'status' => 'clicked',
        ];
    }

    /**
     * Create a pre-configured server for browser testing.
     */
    public static function forBrowserTesting(): self
    {
        $server = new self();

        $server->setResponse('Browser.launch', [
            'browserId' => 'browser_1',
            'type' => 'chromium',
        ]);

        $server->setResponse('Browser.contexts', [
            'contexts' => [],
        ]);

        $server->setResponse('Browser.close', [
            'status' => 'closed',
        ]);

        return $server;
    }

    /**
     * Create a pre-configured server for page testing.
     */
    public static function forPageTesting(): self
    {
        $server = self::forBrowserTesting();

        $server->setResponse('Page.setViewportSize', [
            'status' => 'resized',
        ]);

        $server->setResponse('Page.reload', [
            'loaderId' => 'reload_1',
        ]);

        $server->setResponse('Page.goBack', [
            'loaderId' => 'back_1',
        ]);

        $server->setResponse('Page.goForward', [
            'loaderId' => 'forward_1',
        ]);

        return $server;
    }

    /**
     * Create a server that simulates errors.
     */
    public static function withErrors(): self
    {
        $server = new self();

        $server->setErrorResponse('Page.goto', 'Navigation timeout', 408);
        $server->setErrorResponse('Locator.click', 'Element not found', 404);

        return $server;
    }
}
