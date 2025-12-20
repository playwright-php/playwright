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

namespace Playwright\Page;

use Playwright\API\APIRequestContextInterface;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Clock\ClockInterface;
use Playwright\Clock\NullClock;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Console\ConsoleMessage;
use Playwright\Dialog\Dialog;
use Playwright\Event\EventDispatcherInterface;
use Playwright\Exception\NetworkException;
use Playwright\Exception\PlaywrightException;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Exception\RuntimeException;
use Playwright\Exception\TimeoutException;
use Playwright\Frame\Frame;
use Playwright\Frame\FrameInterface;
use Playwright\Frame\FrameLocator;
use Playwright\Frame\FrameLocatorInterface;
use Playwright\Input\Keyboard;
use Playwright\Input\KeyboardInterface;
use Playwright\Input\ModifierKey;
use Playwright\Input\Mouse;
use Playwright\Input\MouseInterface;
use Playwright\Locator\Locator;
use Playwright\Locator\LocatorInterface;
use Playwright\Locator\Options\GetByRoleOptions;
use Playwright\Locator\Options\LocatorOptions;
use Playwright\Network\Request;
use Playwright\Network\Response;
use Playwright\Network\ResponseInterface;
use Playwright\Network\Route;
use Playwright\Page\Options\ClickOptions;
use Playwright\Page\Options\FrameQueryOptions;
use Playwright\Page\Options\GotoOptions;
use Playwright\Page\Options\NavigationHistoryOptions;
use Playwright\Page\Options\ScreenshotOptions;
use Playwright\Page\Options\ScriptTagOptions;
use Playwright\Page\Options\SetContentOptions;
use Playwright\Page\Options\SetInputFilesOptions;
use Playwright\Page\Options\StyleTagOptions;
use Playwright\Page\Options\TypeOptions;
use Playwright\Page\Options\WaitForLoadStateOptions;
use Playwright\Page\Options\WaitForPopupOptions;
use Playwright\Page\Options\WaitForResponseOptions;
use Playwright\Page\Options\WaitForSelectorOptions;
use Playwright\Page\Options\WaitForUrlOptions;
use Playwright\Screenshot\ScreenshotHelper;
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Page implements PageInterface, EventDispatcherInterface
{
    public readonly ClockInterface $clock;

    public readonly KeyboardInterface $keyboard;

    public readonly MouseInterface $mouse;

    private PageEventHandlerInterface $eventHandler;

    private LoggerInterface $logger;

    private ?APIRequestContextInterface $apiRequestContext = null;

    private bool $isClosed = false;

    /**
     * @var array<string, true>
     */
    private array $handledDialogs = [];

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

        if (\method_exists($this->context, 'clock')) {
            /** @var ClockInterface $ctxClock */
            $ctxClock = $this->context->clock();
            $this->clock = $ctxClock;
        } else {
            $this->clock = new NullClock();
        }

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
                    $dialogId = $params['dialogId'];

                    if (isset($this->handledDialogs[$dialogId])) {
                        $this->logger->debug('Ignoring duplicate dialog event', ['dialogId' => $dialogId]);

                        return;
                    }

                    $this->handledDialogs[$dialogId] = true;

                    $defaultValue = $params['defaultValue'] ?? null;
                    $defaultValue = is_string($defaultValue) ? $defaultValue : null;
                    $dialog = $this->createDialog(
                        $dialogId,
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
            case 'close':
                $this->isClosed = true;
                $this->eventHandler->publicEmit('close', []);
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

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function locator(string $selector, array|LocatorOptions $options = []): LocatorInterface
    {
        return new Locator(
            $this->transport,
            $this->pageId,
            $selector,
            null,
            null,
            $this->normalizeLocatorOptions($options)
        );
    }

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByAltText(string $text, array|LocatorOptions $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('[alt="%s"]', $text), $this->normalizeLocatorOptions($options));
    }

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByLabel(string $text, array|LocatorOptions $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('label:text-is("%s") >> nth=0', $text), $this->normalizeLocatorOptions($options));
    }

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByPlaceholder(string $text, array|LocatorOptions $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('[placeholder="%s"]', $text), $this->normalizeLocatorOptions($options));
    }

    /**
     * @param array<string, mixed>|GetByRoleOptions $options
     */
    public function getByRole(string $role, array|GetByRoleOptions $options = []): LocatorInterface
    {
        return $this->locator($role, $this->normalizeGetByRoleOptions($options));
    }

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByTestId(string $testId, array|LocatorOptions $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('[data-testid="%s"]', $testId), $options);
    }

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByText(string $text, array|LocatorOptions $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('text="%s"', $text), $this->normalizeLocatorOptions($options));
    }

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByTitle(string $text, array|LocatorOptions $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('[title="%s"]', $text), $this->normalizeLocatorOptions($options));
    }

    /**
     * @param array<string, mixed>|GotoOptions $options
     */
    public function goto(string $url, array|GotoOptions $options = []): ?ResponseInterface
    {
        $options = GotoOptions::from($options)->toArray();

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
     * @param string|null                            $path    Screenshot path. If null, auto-generates based on current URL and datetime
     * @param array<string, mixed>|ScreenshotOptions $options Screenshot options (quality, fullPage, etc.)
     *
     * @return string Returns the screenshot file path
     */
    public function screenshot(?string $path = null, array|ScreenshotOptions $options = []): string
    {
        $options = ScreenshotOptions::from($options)->toArray();

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
     * Generate a PDF of the page.
     *
     * @param array<string, mixed>|PdfOptions $options
     */
    public function pdf(?string $path = null, array|PdfOptions $options = []): string
    {
        $options = PdfOptions::from($options);
        $providedPath = $path ?? $options->path();
        $finalPath = $this->resolvePdfPath(is_string($providedPath) ? $providedPath : null);

        $options = $options->withPath($finalPath)->toArray();

        $this->logger->debug('Generating PDF', ['path' => $finalPath, 'options' => $options]);

        try {
            $this->sendCommand('pdf', ['options' => $options]);
            $this->logger->info('PDF saved successfully', ['path' => $finalPath]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate PDF', [
                'path' => $finalPath,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            throw $e;
        }

        return $finalPath;
    }

    /**
     * Generate a PDF and return its binary contents without persisting the file.
     *
     * @param array<string, mixed>|PdfOptions $options
     */
    public function pdfContent(array|PdfOptions $options = []): string
    {
        $options = PdfOptions::from($options);

        if (null !== $options->path()) {
            throw new RuntimeException('Do not provide a "path" option when requesting inline PDF content.');
        }

        $directory = $this->getPdfDirectory();
        ScreenshotHelper::ensureDirectoryExists($directory);

        $tempPath = tempnam($directory, 'pw_pdf_');
        if (false === $tempPath) {
            throw new RuntimeException('Failed to allocate a temporary PDF file.');
        }

        // Remove the placeholder so Playwright can create the file fresh.
        @unlink($tempPath);

        try {
            $this->pdf($tempPath, $options);

            $content = file_get_contents($tempPath);
            if (false === $content) {
                throw new RuntimeException('Unable to read generated PDF content.');
            }

            return $content;
        } finally {
            if (is_string($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
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

    private function getPdfDirectory(): string
    {
        return $this->getScreenshotDirectory();
    }

    private function resolvePdfPath(?string $path): string
    {
        $candidate = null;
        if (is_string($path) && '' !== trim($path)) {
            $candidate = $path;
        }

        if (null !== $candidate) {
            $directory = dirname($candidate) ?: '.';
            ScreenshotHelper::ensureDirectoryExists($directory);

            return $candidate;
        }

        return ScreenshotHelper::generateFilename(
            $this->url(),
            $this->getPdfDirectory(),
            'pdf'
        );
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
     * @param array<string, mixed>|WaitForSelectorOptions $options
     */
    public function waitForSelector(string $selector, array|WaitForSelectorOptions $options = []): LocatorInterface
    {
        $options = WaitForSelectorOptions::from($options)->toArray();
        $this->sendCommand('waitForSelector', ['selector' => $selector, 'options' => $options]);

        return $this->locator($selector);
    }

    public function close(): void
    {
        $this->sendCommand('close');

        $this->isClosed = true;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function click(string $selector, array|ClickOptions $options = []): self
    {
        $options = ClickOptions::from($options)->toArray();
        $this->locator($selector)->click($options);

        $this->transport->processEvents();

        return $this;
    }

    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function altClick(string $selector, array|ClickOptions $options = []): self
    {
        $options = ClickOptions::from($options)->toArray();
        $options['modifiers'] = [...((array) ($options['modifiers'] ?? [])), ModifierKey::Alt->value];

        return $this->click($selector, $options);
    }

    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function controlClick(string $selector, array|ClickOptions $options = []): self
    {
        $options = ClickOptions::from($options)->toArray();
        $options['modifiers'] = [...((array) ($options['modifiers'] ?? [])), ModifierKey::Control->value];

        return $this->click($selector, $options);
    }

    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function shiftClick(string $selector, array|ClickOptions $options = []): self
    {
        $options = ClickOptions::from($options)->toArray();
        $options['modifiers'] = [...((array) ($options['modifiers'] ?? [])), ModifierKey::Shift->value];

        return $this->click($selector, $options);
    }

    /**
     * @param array<string, mixed>|TypeOptions $options
     */
    public function type(string $selector, string $text, array|TypeOptions $options = []): self
    {
        $options = TypeOptions::from($options)->toArray();
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
     * @param array<string, mixed>|LocatorOptions $options
     *
     * @return array<string, mixed>
     */
    private function normalizeLocatorOptions(array|LocatorOptions $options): array
    {
        return LocatorOptions::from($options)->toArray();
    }

    /**
     * @param array<string, mixed>|GetByRoleOptions $options
     *
     * @return array<string, mixed>
     */
    private function normalizeGetByRoleOptions(array|GetByRoleOptions $options): array
    {
        return GetByRoleOptions::from($options)->toArray();
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
     * @param array<string, mixed>|NavigationHistoryOptions $options
     */
    public function goBack(array|NavigationHistoryOptions $options = []): self
    {
        $options = NavigationHistoryOptions::from($options)->toArray();
        $this->sendCommand('goBack', ['options' => $options]);

        return $this;
    }

    /**
     * @param array<string, mixed>|NavigationHistoryOptions $options
     */
    public function goForward(array|NavigationHistoryOptions $options = []): self
    {
        $options = NavigationHistoryOptions::from($options)->toArray();
        $this->sendCommand('goForward', ['options' => $options]);

        return $this;
    }

    /**
     * @param array<string, mixed>|NavigationHistoryOptions $options
     */
    public function reload(array|NavigationHistoryOptions $options = []): self
    {
        $options = NavigationHistoryOptions::from($options)->toArray();
        $this->sendCommand('reload', ['options' => $options]);

        return $this;
    }

    /**
     * @param array<string, mixed>|SetContentOptions $options
     */
    public function setContent(string $html, array|SetContentOptions $options = []): self
    {
        $options = SetContentOptions::from($options)->toArray();
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

    public function setDefaultNavigationTimeout(int $timeout): self
    {
        $this->sendCommand('setDefaultNavigationTimeout', ['timeout' => $timeout]);

        return $this;
    }

    public function setDefaultTimeout(int $timeout): self
    {
        $this->sendCommand('setDefaultTimeout', ['timeout' => $timeout]);

        return $this;
    }

    /**
     * @param array<string, mixed>|WaitForLoadStateOptions $options
     */
    public function waitForLoadState(string $state = 'load', array|WaitForLoadStateOptions $options = []): self
    {
        $options = WaitForLoadStateOptions::from($options)->toArray();
        $this->sendCommand('waitForLoadState', ['state' => $state, 'options' => $options]);

        return $this;
    }

    /**
     * @param array<string, mixed>|WaitForUrlOptions $options
     */
    public function waitForURL($url, array|WaitForUrlOptions $options = []): self
    {
        $options = WaitForUrlOptions::from($options)->toArray();
        $this->sendCommand('waitForURL', ['url' => $url, 'options' => $options]);

        $this->transport->processEvents();

        return $this;
    }

    /**
     * @param string|callable                             $url
     * @param array<string, mixed>|WaitForResponseOptions $options
     */
    public function waitForResponse($url, array|WaitForResponseOptions $options = []): ResponseInterface
    {
        $action = null;
        if (is_array($options)) {
            $action = $options['action'] ?? null;
            unset($options['action']);
        }

        $options = WaitForResponseOptions::from($options);
        $response = $this->sendCommand('waitForResponse', ['url' => $url, 'options' => $options->toArray(), 'jsAction' => $action]);

        return $this->createResponse($this->pageId, $response['response']);
    }

    public function addScriptTag(array|ScriptTagOptions $options): self
    {
        $options = ScriptTagOptions::from($options)->toArray();
        $this->sendCommand('addScriptTag', ['options' => $options]);

        return $this;
    }

    public function addStyleTag(array|StyleTagOptions $options): self
    {
        $options = StyleTagOptions::from($options)->toArray();
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
     * @param array{name?: string, url?: string, urlRegex?: string}|FrameQueryOptions $options
     */
    public function frame(array|FrameQueryOptions $options): ?FrameInterface
    {
        $options = FrameQueryOptions::from($options)->toArray();
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
        $params = ['dialogId' => $dialogId, 'accept' => $accept];

        if (null !== $promptText) {
            $params['promptText'] = $promptText;
        }

        $this->sendCommand('handleDialog', $params);
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
     * @param array<string, mixed>|WaitForPopupOptions $options
     */
    public function waitForPopup(callable $action, array|WaitForPopupOptions $options = []): self
    {
        $options = WaitForPopupOptions::from($options)->toArray();
        $timeout = $options['timeout'] ?? 30000;
        $requestId = uniqid('popup_', true);

        if (method_exists($this->transport, 'storePendingCallback')) {
            $this->transport->storePendingCallback($requestId, $action);
        } else {
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
     * @param array<string, mixed>|SetInputFilesOptions $options
     */
    public function setInputFiles(string $selector, array $files, array|SetInputFilesOptions $options = []): self
    {
        $options = SetInputFilesOptions::from($options)->toArray();

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

    public function request(): APIRequestContextInterface
    {
        if (null === $this->apiRequestContext) {
            $this->apiRequestContext = $this->context->request();
        }

        return $this->apiRequestContext;
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
