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
use PlaywrightPHP\Exception\ProtocolErrorException;
use PlaywrightPHP\Exception\RuntimeException;
use PlaywrightPHP\Exception\TimeoutException;
use PlaywrightPHP\Frame\Frame;
use PlaywrightPHP\Frame\FrameInterface;
use PlaywrightPHP\Frame\FrameLocator;
use PlaywrightPHP\Frame\FrameLocatorInterface;
use PlaywrightPHP\Input\Keyboard;
use PlaywrightPHP\Input\KeyboardInterface;
use PlaywrightPHP\Input\ModifierKey;
use PlaywrightPHP\Input\Mouse;
use PlaywrightPHP\Input\MouseInterface;
use PlaywrightPHP\Internal\OwnershipRegistry;
use PlaywrightPHP\Internal\RemoteObject;
use PlaywrightPHP\Locator\Locator;
use PlaywrightPHP\Locator\LocatorInterface;
use PlaywrightPHP\Network\Request;
use PlaywrightPHP\Network\Response;
use PlaywrightPHP\Network\ResponseInterface;
use PlaywrightPHP\Network\Route;
use PlaywrightPHP\Screenshot\ScreenshotHelper;
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

    private RemoteObject $remoteObject;

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
        $this->remoteObject = new PageRemoteObject($this->transport, $this->pageId, 'page');
        OwnershipRegistry::register($this->remoteObject);

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
        $finalPath = $path ?? $options['path'] ?? ScreenshotHelper::generateFilename(
            $this->url(),
            $this->getScreenshotDirectory()
        );

        if (!is_string($finalPath)) {
            throw new RuntimeException('Invalid screenshot path generated');
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

        $now = microtime(true);
        $datetime = date('Ymd_His', (int) $now);
        $milliseconds = sprintf('%03d', ($now - floor($now)) * 1000);

        $urlSlug = ScreenshotHelper::slugifyUrl($currentUrl, 20);
        $suffixSlug = $suffix ? '-'.ScreenshotHelper::slugifyUrl($suffix, 20) : '';

        $filename = sprintf('%s_%s_%s%s.png', $datetime, $milliseconds, $urlSlug, $suffixSlug);
        $path = $screenshotDir.DIRECTORY_SEPARATOR.$filename;

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
        $normalized = self::normalizeForPage($expression);
        $response = $this->sendCommand('evaluate', ['expression' => $normalized, 'arg' => $arg]);

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
        $this->remoteObject->dispose();
    }

    public function isDisposed(): bool
    {
        return $this->remoteObject->isDisposed();
    }

    public function getRemoteObject(): RemoteObject
    {
        return $this->remoteObject;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function click(string $selector, array $options = []): self
    {
        $this->locator($selector)->click($options);

        $this->transport->processEvents();

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function altClick(string $selector, array $options = []): self
    {
        return $this->click($selector, [...$options, 'modifiers' => ModifierKey::Alt]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function controlClick(string $selector, array $options = []): self
    {
        return $this->click($selector, [...$options, 'modifiers' => ModifierKey::Control]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function shiftClick(string $selector, array $options = []): self
    {
        return $this->click($selector, [...$options, 'modifiers' => ModifierKey::Shift]);
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
                $message = is_string($error) ? $error : 'Unknown error';
                $this->logger->error('Playwright server error (non-structured)', ['error' => $error]);
                if (str_contains($message, 'Timeout')) {
                    throw new TimeoutException($message);
                }
                if (str_contains($message, 'net::') || str_contains($message, 'NetworkError')) {
                    throw new NetworkException($message);
                }
                throw new PlaywrightException($message);
            }

            $message = $error['message'] ?? 'Unknown error';
            if (!is_string($message)) {
                $message = 'Unknown error';
            }

            $this->logger->error('Playwright server error', ['error' => $error]);

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

        $validatedCookies = [];
        foreach ($cookies as $cookie) {
            if (!is_array($cookie)) {
                continue;
            }

            $name = $cookie['name'] ?? null;
            $value = $cookie['value'] ?? null;
            $domain = $cookie['domain'] ?? null;
            $path = $cookie['path'] ?? null;
            $expires = $cookie['expires'] ?? null;
            $httpOnly = $cookie['httpOnly'] ?? null;
            $secure = $cookie['secure'] ?? null;
            $sameSite = $cookie['sameSite'] ?? null;

            if (!is_string($name)
                || !is_string($value)
                || !is_string($domain)
                || !is_string($path)
                || !is_int($expires)
                || !is_bool($httpOnly)
                || !is_bool($secure)
                || !is_string($sameSite)
                || !in_array($sameSite, ['Lax', 'None', 'Strict'], true)
            ) {
                continue;
            }

            $validatedCookies[] = [
                'name' => $name,
                'value' => $value,
                'domain' => $domain,
                'path' => $path,
                'expires' => $expires,
                'httpOnly' => $httpOnly,
                'secure' => $secure,
                'sameSite' => $sameSite,
            ];
        }

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
            throw new ProtocolErrorException('Invalid URL response from transport', 0);
        }

        return $url;
    }

    public function title(): string
    {
        $response = $this->sendCommand('title');
        $title = $response['value'];
        if (!is_string($title)) {
            throw new ProtocolErrorException('Invalid title response from transport', 0);
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
            throw new ProtocolErrorException('Invalid viewportSize response from transport', 0);
        }

        /* @var array{width: int, height: int} $viewport */
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

    public function mainFrame(): FrameInterface
    {
        return new Frame($this->transport, $this->pageId, ':root');
    }

    /**
     * @return array<FrameInterface>
     */
    public function frames(): array
    {
        $response = $this->sendCommand('frames');
        $frames = $response['frames'] ?? [];
        if (!\is_array($frames)) {
            return [];
        }

        $result = [];
        foreach ($frames as $frameData) {
            if (\is_array($frameData) && isset($frameData['selector']) && \is_string($frameData['selector'])) {
                $result[] = new Frame($this->transport, $this->pageId, $frameData['selector']);
            }
        }

        return $result;
    }

    /**
     * @param array{name?: string, url?: string, urlRegex?: string} $options
     */
    public function frame(array $options): ?FrameInterface
    {
        $response = $this->sendCommand('frame', ['options' => $options]);
        $selector = $response['selector'] ?? null;
        if (\is_string($selector)) {
            return new Frame($this->transport, $this->pageId, $selector);
        }

        return null;
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
    public function waitForPopup(callable $action, array $options = []): self
    {
        $timeout = $options['timeout'] ?? 30000;
        $requestId = uniqid('popup_', true);

        if (method_exists($this->transport, 'storePendingCallback')) {
            $this->transport->storePendingCallback($requestId, $action);
        } else {
            // Fallback for transports that don't support callbacks
            $action();
        }

        $response = $this->transport->send([
            'action' => 'page.waitForPopup',
            'pageId' => $this->pageId,
            'timeout' => $timeout,
            'requestId' => $requestId,
        ]);

        $popupPageId = $response['popupPageId'] ?? null;
        if (!is_string($popupPageId)) {
            throw new TimeoutException('No popup was created within the timeout period');
        }

        return new self($this->transport, $this->context, $popupPageId, $this->config, $this->logger);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setInputFiles(string $selector, array $files, array $options = []): self
    {
        $this->logger->debug('Setting input files', ['selector' => $selector, 'files' => $files]);

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

    private static function normalizeForPage(string $expression): string
    {
        $trimmed = ltrim($expression);

        if (self::isFunctionLike($trimmed)) {
            return $expression;
        }

        if (self::startsWithReturn($trimmed)) {
            return '(arg) => { '.$trimmed.' }';
        }

        return $expression;
    }

    private static function isFunctionLike(string $s): bool
    {
        // Detect common JS function patterns: function, async function, arrow functions
        return (bool) preg_match('/^((async\s+)?function\b|\([^)]*\)\s*=>|[A-Za-z_$][A-Za-z0-9_$]*\s*=>|async\s*\([^)]*\)\s*=>)/', $s);
    }

    private static function startsWithReturn(string $s): bool
    {
        return (bool) preg_match('/^return\b/', $s);
    }

    /**
     * Helper method to validate transport data for Request creation.
     *
     * @return array<string, mixed>
     */
    private function validateRequestData(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ProtocolErrorException('Invalid request data from transport', 0);
        }
        $result = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                throw new ProtocolErrorException('Invalid request data from transport: non-string key', 0);
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Helper method to validate transport data for Response creation.
     *
     * @return array<string, mixed>
     */
    private function validateResponseData(mixed $data): array
    {
        if (!is_array($data)) {
            throw new ProtocolErrorException('Invalid response data from transport', 0);
        }
        $result = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                throw new ProtocolErrorException('Invalid response data from transport: non-string key', 0);
            }
            $result[$key] = $value;
        }

        return $result;
    }
}

/**
 * RemoteObject implementation for Page.
 */
class PageRemoteObject extends RemoteObject
{
    protected function onDispose(): void
    {
        $this->transport->send([
            'action' => 'page.close',
            'pageId' => $this->remoteId,
        ]);
    }
}
