<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Page;

use PlaywrightPHP\Browser\BrowserContextInterface;
use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Console\ConsoleMessage;
use PlaywrightPHP\Dialog\Dialog;
use PlaywrightPHP\Event\EventDispatcherInterface;
use PlaywrightPHP\Exception\NetworkException;
use PlaywrightPHP\Exception\PlaywrightException;
use PlaywrightPHP\Exception\TimeoutException;
use PlaywrightPHP\FrameLocator\FrameLocator;
use PlaywrightPHP\FrameLocator\FrameLocatorInterface;
use PlaywrightPHP\Input\Keyboard;
use PlaywrightPHP\Input\KeyboardInterface;
use PlaywrightPHP\Input\Mouse;
use PlaywrightPHP\Input\MouseInterface;
use PlaywrightPHP\Locator\Locator;
use PlaywrightPHP\Locator\LocatorInterface;
use PlaywrightPHP\Network\Request;
use PlaywrightPHP\Network\Response;
use PlaywrightPHP\Network\ResponseInterface;
use PlaywrightPHP\Network\Route;
use PlaywrightPHP\Support\ScreenshotHelper;
use PlaywrightPHP\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class Page implements PageInterface, EventDispatcherInterface
{
    private KeyboardInterface $keyboard;
    private MouseInterface $mouse;
    private PageEventHandlerInterface $eventHandler;
    private LoggerInterface $logger;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly BrowserContextInterface $context,
        private readonly string $pageId,
        private readonly ?PlaywrightConfig $config = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->keyboard = new Keyboard($this->transport, $this->pageId);
        $this->mouse = new Mouse($this->transport, $this->pageId);
        $this->eventHandler = new PageEventHandler();

        if (method_exists($this->transport, 'addEventDispatcher')) {
            $this->transport->addEventDispatcher($this->pageId, $this);
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public function dispatchEvent(string $eventName, array $params): void
    {
        switch ($eventName) {
            case 'dialog':
                if (is_string($params['dialogId']) && is_string($params['type']) && is_string($params['message'])) {
                    $defaultValue = $params['defaultValue'] ?? null;
                    $defaultValue = is_string($defaultValue) ? $defaultValue : null;
                    $dialog = $this->createDialog(
                        $params['dialogId'],
                        $params['type'],
                        $params['message'],
                        $defaultValue
                    );
                    $this->eventHandler->publicEmit('dialog', [$dialog]);
                }
                break;
            case 'console':
                $this->eventHandler->publicEmit('console', [$this->createConsoleMessage($params)]);
                break;
            case 'request':
                if (is_array($params['request'])) {
                    $this->eventHandler->publicEmit('request', [$this->createRequest($params['request'])]);
                }
                break;
            case 'response':
                if (is_array($params['response'])) {
                    $this->eventHandler->publicEmit('response', [$this->createResponse($this->pageId, $params['response'])]);
                }
                break;
            case 'requestfailed':
                if (is_array($params['request'])) {
                    $this->eventHandler->publicEmit('requestfailed', [$this->createRequest($params['request'])]);
                }
                break;
            case 'route':
                if (is_string($params['routeId']) && is_array($params['request'])) {
                    $route = $this->createRoute(
                        $this->pageId,
                        $params['routeId'],
                        $params['request']
                    );
                } else {
                    break;
                }
                $this->eventHandler->publicEmit('route', [$route]);
                break;
            default:
                $this->eventHandler->publicEmit($eventName, $params);
        }
    }

    public function keyboard(): KeyboardInterface
    {
        return $this->keyboard;
    }

    public function mouse(): MouseInterface
    {
        return $this->mouse;
    }

    public function events(): PageEventHandlerInterface
    {
        return $this->eventHandler;
    }

    public function locator(string $selector): LocatorInterface
    {
        return new Locator($this->transport, $this->pageId, $selector);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function goto(string $url, array $options = []): ?ResponseInterface
    {
        $this->logger->debug('Navigating to URL', ['url' => $url, 'options' => $options]);

        try {
            $response = $this->sendCommand('goto', ['url' => $url, 'options' => $options]);
            $this->logger->info('Successfully navigated to URL', ['url' => $url]);

            $responseData = $response['response'] ?? null;

            return $responseData && is_array($responseData) ? $this->createResponse($this->pageId, $responseData) : null;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to navigate to URL', [
                'url' => $url,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Take a screenshot of the page.
     *
     * @param string|null          $path    Screenshot path. If null, auto-generates based on current URL and datetime
     * @param array<string, mixed> $options Screenshot options (quality, fullPage, etc.)
     *
     * @return string Returns the screenshot file path
     */
    public function screenshot(?string $path = null, array $options = []): string
    {
        // Determine the final path: explicit parameter > options > auto-generated
        $finalPath = $path ?? $options['path'] ?? ScreenshotHelper::generateFilename(
            $this->url(),
            $this->getScreenshotDirectory()
        );

        // Ensure we have a string path
        if (!is_string($finalPath)) {
            throw new \RuntimeException('Invalid screenshot path generated');
        }

        $this->logger->debug('Taking screenshot', ['path' => $finalPath, 'options' => $options]);

        $options['path'] = $finalPath;

        try {
            $this->sendCommand('screenshot', ['options' => $options]);
            $this->logger->info('Screenshot saved successfully', ['path' => $finalPath]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to take screenshot', [
                'path' => $finalPath,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }

        return $finalPath;
    }

    /**
     * Take an auto-generated screenshot with custom suffix.
     *
     * @param string               $suffix  Custom suffix for the filename (will be slugified)
     * @param array<string, mixed> $options Screenshot options
     *
     * @return string The generated screenshot path
     */
    public function screenshotAuto(string $suffix = '', array $options = []): string
    {
        $currentUrl = $this->url();
        $screenshotDir = $this->getScreenshotDirectory();

        // Generate filename with custom suffix
        $now = microtime(true);
        $datetime = date('Ymd_His', (int) $now);
        $milliseconds = sprintf('%03d', ($now - floor($now)) * 1000);

        $urlSlug = ScreenshotHelper::slugifyUrl($currentUrl, 20); // Shorter for custom suffix
        $suffixSlug = $suffix ? '-'.ScreenshotHelper::slugifyUrl($suffix, 20) : '';

        $filename = sprintf('%s_%s_%s%s.png', $datetime, $milliseconds, $urlSlug, $suffixSlug);
        $path = $screenshotDir.DIRECTORY_SEPARATOR.$filename;

        // Ensure directory exists
        ScreenshotHelper::ensureDirectoryExists($screenshotDir);

        $options['path'] = $path;
        $this->sendCommand('screenshot', ['options' => $options]);

        return $path;
    }

    /**
     * Get the effective screenshot directory.
     */
    private function getScreenshotDirectory(): string
    {
        if (null !== $this->config) {
            return $this->config->getScreenshotDirectory();
        }

        // Fallback if no config provided
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'playwright';
    }

    public function content(): ?string
    {
        $response = $this->sendCommand('content');
        $content = $response['content'] ?? null;

        return is_string($content) ? $content : null;
    }

    public function evaluate(string $expression, mixed $arg = null): mixed
    {
        $response = $this->sendCommand('evaluate', ['expression' => $expression, 'arg' => $arg]);

        return $response['result'] ?? null;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForSelector(string $selector, array $options = []): LocatorInterface
    {
        $this->sendCommand('waitForSelector', ['selector' => $selector, 'options' => $options]);

        return $this->locator($selector);
    }

    public function close(): void
    {
        $this->sendCommand('close');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function click(string $selector, array $options = []): self
    {
        $this->locator($selector)->click($options);

        // Process any pending events after click (may trigger navigation)
        $this->transport->processEvents();

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function type(string $selector, string $text, array $options = []): self
    {
        $this->locator($selector)->type($text, $options);

        return $this;
    }

    /**
     * Opens Playwright Inspector and pauses script execution.
     */
    public function pause(): self
    {
        $this->sendCommand('pause');

        return $this;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function sendCommand(string $action, array $params = []): array
    {
        $payload = array_merge($params, [
            'action' => 'page.'.$action,
            'pageId' => $this->pageId,
        ]);

        $response = $this->transport->send($payload);

        if (isset($response['error'])) {
            $error = $response['error'];
            if (!is_array($error)) {
                throw new PlaywrightException('Invalid error response from transport');
            }

            $message = $error['message'] ?? 'Unknown error';
            if (!is_string($message)) {
                $message = 'Unknown error';
            }

            // Log the error for debugging
            $this->logger->error('Playwright server error', ['error' => $error]);

            // Handle specific Playwright errors
            if (str_contains($message, 'Target page, context or browser has been closed')) {
                throw new PlaywrightException('Browser context has been closed');
            }

            $errorName = $error['name'] ?? null;
            if (is_string($errorName)) {
                switch ($errorName) {
                    case 'TimeoutError':
                        throw new TimeoutException($message);
                    case 'NetworkError':
                        throw new NetworkException($message);
                }
            }

            if (str_contains($message, 'Timeout')) {
                throw new TimeoutException($message);
            }

            if (str_contains($message, 'net::')) {
                throw new NetworkException($message);
            }

            throw new PlaywrightException($message);
        }

        return $response;
    }

    public function bringToFront(): self
    {
        $this->sendCommand('bringToFront');

        return $this;
    }

    public function context(): BrowserContextInterface
    {
        return $this->context;
    }

    public function cookies(?array $urls = null): array
    {
        $response = $this->sendCommand('cookies', ['urls' => $urls]);
        $cookies = $response['cookies'];

        if (!is_array($cookies)) {
            return [];
        }

        // Validate cookie structure
        $validatedCookies = [];
        foreach ($cookies as $cookie) {
            if (is_array($cookie)) {
                // Basic validation - add more specific checks if needed
                $validatedCookies[] = $cookie;
            }
        }

        /** @var array<array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: 'Lax'|'None'|'Strict'}> $validatedCookies */
        return $validatedCookies;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function goBack(array $options = []): self
    {
        $this->sendCommand('goBack', ['options' => $options]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function goForward(array $options = []): self
    {
        $this->sendCommand('goForward', ['options' => $options]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function reload(array $options = []): self
    {
        $this->sendCommand('reload', ['options' => $options]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setContent(string $html, array $options = []): self
    {
        $this->sendCommand('setContent', ['html' => $html, 'options' => $options]);

        return $this;
    }

    public function url(): string
    {
        $response = $this->sendCommand('url');
        $url = $response['value'];
        if (!is_string($url)) {
            throw new \RuntimeException('Invalid URL response from transport');
        }

        return $url;
    }

    public function title(): string
    {
        $response = $this->sendCommand('title');
        $title = $response['value'];
        if (!is_string($title)) {
            throw new \RuntimeException('Invalid title response from transport');
        }

        return $title;
    }

    /**
     * @return array{width: int, height: int}|null
     */
    public function viewportSize(): ?array
    {
        $response = $this->sendCommand('viewportSize');
        $viewport = $response['value'];

        if (null === $viewport) {
            return null;
        }

        if (!is_array($viewport)
            || !array_key_exists('width', $viewport)
            || !array_key_exists('height', $viewport)
            || !is_int($viewport['width'])
            || !is_int($viewport['height'])
        ) {
            throw new \RuntimeException('Invalid viewportSize response from transport');
        }

        /** @var array{width: int, height: int} $viewport */
        return ['width' => $viewport['width'], 'height' => $viewport['height']];
    }

    public function setViewportSize(int $width, int $height): self
    {
        $this->sendCommand('setViewportSize', ['size' => ['width' => $width, 'height' => $height]]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForLoadState(string $state = 'load', array $options = []): self
    {
        $this->sendCommand('waitForLoadState', ['state' => $state, 'options' => $options]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForURL($url, array $options = []): self
    {
        $this->sendCommand('waitForURL', ['url' => $url, 'options' => $options]);

        // Process any pending events after navigation completes
        $this->transport->processEvents();

        return $this;
    }

    /**
     * @param string|callable      $url
     * @param array<string, mixed> $options
     */
    public function waitForResponse($url, array $options = []): ResponseInterface
    {
        $action = $options['action'] ?? null;
        unset($options['action']);

        $response = $this->sendCommand('waitForResponse', ['url' => $url, 'options' => $options, 'jsAction' => $action]);

        return $this->createResponse($this->pageId, $response['response']);
    }

    public function addScriptTag(array $options): self
    {
        $this->sendCommand('addScriptTag', ['options' => $options]);

        return $this;
    }

    public function addStyleTag(array $options): self
    {
        $this->sendCommand('addStyleTag', ['options' => $options]);

        return $this;
    }

    public function frameLocator(string $selector): FrameLocatorInterface
    {
        return new FrameLocator($this->transport, $this->pageId, $selector);
    }

    public function route(string $url, callable $handler): void
    {
        $this->eventHandler->onRoute($handler);
        $this->sendCommand('route', ['url' => $url]);
    }

    public function unroute(string $url, ?callable $handler = null): void
    {
        $this->context->unroute($url, $handler);
    }

    public function handleDialog(string $dialogId, bool $accept, ?string $promptText = null): void
    {
        $this->sendCommand('handleDialog', ['dialogId' => $dialogId, 'accept' => $accept, 'promptText' => $promptText]);
    }

    public function getPageIdForTransport(): string
    {
        return $this->pageId;
    }

    public function waitForEvents(): void
    {
        $this->transport->sendAsync([
            'action' => 'page.waitForEvents',
            'pageId' => $this->pageId,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setInputFiles(string $selector, array $files, array $options = []): self
    {
        $this->logger->debug('Setting input files', ['selector' => $selector, 'files' => $files]);

        // Validate that all files exist
        foreach ($files as $file) {
            if (!\file_exists($file)) {
                throw new PlaywrightException(\sprintf('File not found: %s', $file));
            }
        }

        try {
            $this->locator($selector)->setInputFiles($files, $options);
            $this->logger->info('Successfully set input files', ['selector' => $selector, 'fileCount' => \count($files)]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to set input files', [
                'selector' => $selector,
                'files' => $files,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }

        return $this;
    }

    /**
     * Create a Response object from transport data.
     */
    private function createResponse(string $pageId, mixed $data): Response
    {
        return new Response($this->transport, $pageId, $this->validateResponseData($data));
    }

    /**
     * Create a Request object from transport data.
     */
    private function createRequest(mixed $data): Request
    {
        return new Request($this->validateRequestData($data));
    }

    /**
     * Create a Route object from transport data.
     */
    private function createRoute(string $contextId, string $routeId, mixed $requestData): Route
    {
        return new Route($this->transport, $routeId, $this->validateRequestData($requestData));
    }

    /**
     * Create a Dialog object from transport data.
     */
    private function createDialog(string $dialogId, string $type, string $message, ?string $defaultValue): Dialog
    {
        return new Dialog($this, $dialogId, $type, $message, $defaultValue);
    }

    /**
     * Create a ConsoleMessage object from transport data.
     *
     * @param array<string, mixed> $params
     */
    private function createConsoleMessage(array $params): ConsoleMessage
    {
        return new ConsoleMessage($params);
    }

    /**
     * Helper method to validate transport data for Request creation.
     *
     * @return array<string, mixed>
     */
    private function validateRequestData(mixed $data): array
    {
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid request data from transport');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * Helper method to validate transport data for Response creation.
     *
     * @return array<string, mixed>
     */
    private function validateResponseData(mixed $data): array
    {
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid response data from transport');
        }

        /** @var array<string, mixed> $data */
        return $data;
    }
}
