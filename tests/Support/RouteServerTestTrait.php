<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Support;

use PlaywrightPHP\Browser\BrowserContextInterface;
use PlaywrightPHP\Network\RouteInterface;
use PlaywrightPHP\Page\PageInterface;

/**
 * Serves test content without starting a real HTTP process by using
 * Playwright routing to fulfill requests.
 */
trait RouteServerTestTrait
{
    /** @var array<string, string> */
    private array $routeFiles = [];

    private string $routeServerHost = 'localhost';
    private int $routeServerPort = 31817;
    private bool $routeServerInstalled = false;

    /**
     * Install a route-based "server" for a page with given files.
     *
     * @param PageInterface        $page  Playwright page to attach routes to
     * @param array<string,string> $files Map of paths (e.g., '/index.html') to content
     * @param string               $host  Hostname to use in URLs (defaults to localhost)
     * @param int|null             $port  Optional port to embed in URLs (no socket opened)
     */
    protected function installRouteServer(PageInterface $page, array $files, string $host = 'localhost', ?int $port = null): void
    {
        $this->routeFiles = $files;
        $this->routeServerHost = $host;
        if (null !== $port) {
            $this->routeServerPort = $port;
        }

        if ($this->routeServerInstalled) {
            return;
        }

        $page->route('**/*', function (RouteInterface $route): void {
            $url = $route->request()->url();
            $path = (string) (parse_url($url, PHP_URL_PATH) ?? '/');
            if ('' === $path) {
                $path = '/';
            }

            if ('/' === $path) {
                $candidate = '/index.html';
                if (isset($this->routeFiles[$candidate])) {
                    $path = $candidate;
                }
            }

            $body = $this->routeFiles[$path] ?? null;
            $contentType = $this->guessContentType($path);

            if (null === $body) {
                $route->fulfill([
                    'status' => 404,
                    'headers' => ['content-type' => 'text/plain; charset=utf-8'],
                    'body' => 'Not Found: '.$path,
                ]);

                return;
            }

            $route->fulfill([
                'status' => 200,
                'headers' => ['content-type' => $contentType],
                'body' => $body,
            ]);
        });

        $this->routeServerInstalled = true;
    }

    /**
     * Install route server at the BrowserContext level. Page-level routes
     * take precedence over context-level routes, so tests can override.
     *
     * @param array<string,string> $files
     */
    protected function installContextRouteServer(BrowserContextInterface $context, array $files, string $host = 'localhost', ?int $port = null): void
    {
        $this->routeFiles = $files;
        $this->routeServerHost = $host;
        if (null !== $port) {
            $this->routeServerPort = $port;
        }

        if ($this->routeServerInstalled) {
            return;
        }

        $context->route('**/*', function (RouteInterface $route): void {
            $url = $route->request()->url();
            $path = (string) (parse_url($url, PHP_URL_PATH) ?? '/');
            if ('' === $path) {
                $path = '/';
            }

            if ('/' === $path) {
                $candidate = '/index.html';
                if (isset($this->routeFiles[$candidate])) {
                    $path = $candidate;
                }
            }

            $body = $this->routeFiles[$path] ?? null;
            $contentType = $this->guessContentType($path);

            if (null === $body) {
                $route->fulfill([
                    'status' => 404,
                    'headers' => ['content-type' => 'text/plain; charset=utf-8'],
                    'body' => 'Not Found: '.$path,
                ]);

                return;
            }

            $route->fulfill([
                'status' => 200,
                'headers' => ['content-type' => $contentType],
                'body' => $body,
            ]);
        });

        $this->routeServerInstalled = true;
    }

    /** Build a URL under the route server host/port. */
    protected function routeUrl(string $path = '/'): string
    {
        $path = '/' === $path ? '/' : '/'.ltrim($path, '/');

        return sprintf('http://%s:%d%s', $this->routeServerHost, $this->routeServerPort, $path);
    }

    private function guessContentType(string $path): string
    {
        $lower = strtolower($path);

        return match (true) {
            str_ends_with($lower, '.html'), '/' === $path => 'text/html; charset=utf-8',
            str_ends_with($lower, '.css') => 'text/css; charset=utf-8',
            str_ends_with($lower, '.js') => 'application/javascript; charset=utf-8',
            str_ends_with($lower, '.json') => 'application/json; charset=utf-8',
            default => 'text/plain; charset=utf-8',
        };
    }
}
