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
use Playwright\Console\ConsoleMessage;
use Playwright\Frame\FrameInterface;
use Playwright\Frame\FrameLocatorInterface;
use Playwright\Input\KeyboardInterface;
use Playwright\Input\MouseInterface;
use Playwright\Input\TouchscreenInterface;
use Playwright\JSHandle\JSHandleInterface;
use Playwright\Locator\LocatorInterface;
use Playwright\Locator\Options\GetByRoleOptions;
use Playwright\Locator\Options\LocatorOptions;
use Playwright\Network\RequestInterface;
use Playwright\Network\ResponseInterface;
use Playwright\Page\Options\ClickOptions;
use Playwright\Page\Options\DragAndDropOptions;
use Playwright\Page\Options\EmulateMediaOptions;
use Playwright\Page\Options\FrameQueryOptions;
use Playwright\Page\Options\GotoOptions;
use Playwright\Page\Options\NavigationHistoryOptions;
use Playwright\Page\Options\PdfOptions;
use Playwright\Page\Options\ScreenshotOptions;
use Playwright\Page\Options\ScriptTagOptions;
use Playwright\Page\Options\SetContentOptions;
use Playwright\Page\Options\SetInputFilesOptions;
use Playwright\Page\Options\StyleTagOptions;
use Playwright\Page\Options\TypeOptions;
use Playwright\Page\Options\WaitForFunctionOptions;
use Playwright\Page\Options\WaitForLoadStateOptions;
use Playwright\Page\Options\WaitForPopupOptions;
use Playwright\Page\Options\WaitForRequestOptions;
use Playwright\Page\Options\WaitForResponseOptions;
use Playwright\Page\Options\WaitForSelectorOptions;
use Playwright\Page\Options\WaitForUrlOptions;
use Playwright\Regex;
use Playwright\WebStorage\WebStorageInterface;

interface PageInterface
{
    /**
     * Opens Playwright Inspector and pauses script execution.
     */
    public function pause(): self;

    /**
     * Returns a handle to the result instead of a serialized copy, so a DOM node
     * or a live object survives the round trip.
     *
     * Dispose the handle once done with it, otherwise it pins its target until
     * the page closes.
     */
    public function evaluateHandle(string $expression, mixed $arg = null): JSHandleInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function locator(string $selector, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByAltText(string|Regex $text, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByLabel(string|Regex $text, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByPlaceholder(string|Regex $text, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|GetByRoleOptions $options
     */
    public function getByRole(string $role, array|GetByRoleOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByTestId(string $testId, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByText(string|Regex $text, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByTitle(string|Regex $text, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|GotoOptions $options
     */
    public function goto(string $url, array|GotoOptions $options = []): ?ResponseInterface;

    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function click(string $selector, array|ClickOptions $options = []): self;

    /**
     * @param array<string, mixed>|DragAndDropOptions $options
     */
    public function dragAndDrop(string $source, string $target, array|DragAndDropOptions $options = []): self;

    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function altClick(string $selector, array|ClickOptions $options = []): self;

    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function controlClick(string $selector, array|ClickOptions $options = []): self;

    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function shiftClick(string $selector, array|ClickOptions $options = []): self;

    /**
     * @param array<string, mixed>|TypeOptions $options
     */
    public function type(string $selector, string $text, array|TypeOptions $options = []): self;

    /**
     * @param array<string, mixed>|ScreenshotOptions $options
     */
    public function screenshot(?string $path = null, array|ScreenshotOptions $options = []): string;

    /**
     * @param array<string, mixed>|PdfOptions $options
     */
    public function pdf(?string $path = null, array|PdfOptions $options = []): string;

    /**
     * @param array<string, mixed>|PdfOptions $options
     */
    public function pdfContent(array|PdfOptions $options = []): string;

    public function content(): ?string;

    /**
     * Console messages already recorded for this page, oldest first.
     *
     * Unlike the console event, this reads history, so messages logged before
     * any listener existed are still returned. The default filter keeps only
     * what was logged since the last navigation.
     *
     * @param array{filter?: 'all'|'since-navigation'} $options
     *
     * @return array<ConsoleMessage>
     */
    public function consoleMessages(array $options = []): array;

    /**
     * Discards the recorded console history without touching event listeners.
     */
    public function clearConsoleMessages(): self;

    /**
     * Discards the uncaught page errors recorded so far.
     */
    public function clearPageErrors(): self;

    /**
     * Queues a script to run before any of the page's own scripts.
     *
     * It runs again on every navigation and in every frame the page creates, so
     * it is the place to stub an API or seed state a page reads at startup.
     */
    public function addInitScript(string $script): self;

    public function evaluate(string $expression, mixed $arg = null): mixed;

    /**
     * Overrides the CSS media type and media features the page reports.
     *
     * An omitted feature keeps whatever override is in place; pass
     * 'no-override' to give it back to the browser default.
     *
     * @param array<string, mixed>|EmulateMediaOptions $options
     */
    public function emulateMedia(array|EmulateMediaOptions $options = []): self;

    /**
     * Asks the browser to collect garbage, so a WeakRef held by the page can be
     * observed as cleared. Collection is best effort, never guaranteed.
     */
    public function requestGC(): self;

    /**
     * @param array<string, mixed>|WaitForSelectorOptions $options
     */
    public function waitForSelector(string $selector, array|WaitForSelectorOptions $options = []): LocatorInterface;

    public function close(): void;

    public function isClosed(): bool;

    public function bringToFront(): self;

    public function context(): BrowserContextInterface;

    /**
     * @param array<string>|null $urls
     *
     * @return array<array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool,
     *                           secure: bool, sameSite: 'Strict'|'Lax'|'None'}>
     */
    public function cookies(?array $urls = null): array;

    /**
     * @param array<string, mixed>|NavigationHistoryOptions $options
     */
    public function goBack(array|NavigationHistoryOptions $options = []): self;

    /**
     * @param array<string, mixed>|NavigationHistoryOptions $options
     */
    public function goForward(array|NavigationHistoryOptions $options = []): self;

    /**
     * @param array<string, mixed>|NavigationHistoryOptions $options
     */
    public function reload(array|NavigationHistoryOptions $options = []): self;

    /**
     * @param array<string, mixed>|SetContentOptions $options
     */
    public function setContent(string $html, array|SetContentOptions $options = []): self;

    public function url(): string;

    public function title(): string;

    /**
     * @return array{width: int, height: int}|null
     */
    public function viewportSize(): ?array;

    public function setViewportSize(int $width, int $height): self;

    public function setDefaultNavigationTimeout(int $timeout): self;

    public function setDefaultTimeout(int $timeout): self;

    /**
     * @param array<string, string> $headers
     */
    public function setExtraHTTPHeaders(array $headers): self;

    /**
     * @param array<string, mixed>|WaitForLoadStateOptions $options
     */
    public function waitForLoadState(string $state = 'load', array|WaitForLoadStateOptions $options = []): self;

    /**
     * @param array<string, mixed>|WaitForFunctionOptions $options
     */
    public function waitForFunction(string $pageFunction, mixed $arg = null, array|WaitForFunctionOptions $options = []): self;

    /**
     * @param string|callable                        $url
     * @param array<string, mixed>|WaitForUrlOptions $options
     */
    public function waitForURL($url, array|WaitForUrlOptions $options = []): self;

    /**
     * @param array{url?: string, path?: string, content?: string, type?: string}|ScriptTagOptions $options
     */
    public function addScriptTag(array|ScriptTagOptions $options): self;

    /**
     * @param array{url?: string, path?: string, content?: string}|StyleTagOptions $options
     */
    public function addStyleTag(array|StyleTagOptions $options): self;

    public function frameLocator(string $selector): FrameLocatorInterface;

    public function keyboard(): KeyboardInterface;

    public function mouse(): MouseInterface;

    /**
     * Touch input for this page. The owning context must have been created with
     * hasTouch enabled, otherwise the browser ignores the events.
     */
    public function touchscreen(): TouchscreenInterface;

    /**
     * The `localStorage` of the page's current origin.
     */
    public function localStorage(): WebStorageInterface;

    /**
     * The `sessionStorage` of the page's current origin.
     */
    public function sessionStorage(): WebStorageInterface;

    public function events(): PageEventHandlerInterface;

    public function route(string $url, callable $handler): void;

    public function unroute(string $url, ?callable $handler = null): void;

    /**
     * Removes every route registered on this page in one call.
     *
     * 'behavior' decides what happens to handlers still running: wait for them,
     * wait but swallow their errors, or return without waiting.
     *
     * @param array{behavior?: 'default'|'wait'|'ignoreErrors'} $options
     */
    public function unrouteAll(array $options = []): void;

    public function handleDialog(string $dialogId, bool $accept, ?string $promptText = null): void;

    public function getPageIdForTransport(): string;

    public function waitForEvents(): void;

    /**
     * @param array<string, mixed>|WaitForPopupOptions $options
     */
    public function waitForPopup(callable $action, array|WaitForPopupOptions $options = []): self;

    /**
     * @param string|callable                             $url
     * @param array<string, mixed>|WaitForResponseOptions $options
     */
    public function waitForResponse($url, array|WaitForResponseOptions $options = []): ResponseInterface;

    /**
     * Waits for a request whose URL matches the given Playwright glob.
     *
     * PHP cannot act while this call blocks, so the trigger travels with it: an
     * 'action' entry in $options holds a JavaScript expression the bridge
     * evaluates once the listener is armed.
     *
     * @param array<string, mixed>|WaitForRequestOptions $options
     */
    public function waitForRequest(string $url, array|WaitForRequestOptions $options = []): RequestInterface;

    /**
     * The page that opened this one, or null when nothing did.
     */
    public function opener(): ?PageInterface;

    /**
     * Network requests recorded for this page, oldest first.
     *
     * These are snapshots taken by the bridge, not live handles: members that
     * need the Node object, such as response(), return null on them.
     *
     * @return array<RequestInterface>
     */
    public function requests(): array;

    /**
     * Set files to an input element with type="file".
     *
     * @param string                                    $selector The input selector
     * @param array<string>                             $files    Array of file paths to set
     * @param array<string, mixed>|SetInputFilesOptions $options  Additional options
     */
    public function setInputFiles(string $selector, array $files, array|SetInputFilesOptions $options = []): self;

    /**
     * Get a handle to the main frame.
     */
    public function mainFrame(): FrameInterface;

    /**
     * List top-level child frames of the main frame.
     *
     * @return array<FrameInterface>
     */
    public function frames(): array;

    /**
     * Find a top-level frame by name or URL.
     *
     * @param array{name?: string, url?: string, urlRegex?: string}|FrameQueryOptions $options
     */
    public function frame(array|FrameQueryOptions $options): ?FrameInterface;

    /**
     * API testing helper associated with this page.
     *
     * This method returns the same instance as browserContext.request() on the page's context.
     */
    public function request(): APIRequestContextInterface;
}
