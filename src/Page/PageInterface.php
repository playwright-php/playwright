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
use Playwright\Frame\FrameInterface;
use Playwright\Frame\FrameLocatorInterface;
use Playwright\Input\KeyboardInterface;
use Playwright\Input\MouseInterface;
use Playwright\Locator\LocatorInterface;
use Playwright\Locator\Options\GetByRoleOptions;
use Playwright\Locator\Options\LocatorOptions;
use Playwright\Network\ResponseInterface;
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
use Playwright\Page\Options\WaitForSelectorOptions;
use Playwright\Page\Options\WaitForUrlOptions;

interface PageInterface
{
    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function locator(string $selector, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByAltText(string $text, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByLabel(string $text, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByPlaceholder(string $text, array|LocatorOptions $options = []): LocatorInterface;

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
    public function getByText(string $text, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|LocatorOptions $options
     */
    public function getByTitle(string $text, array|LocatorOptions $options = []): LocatorInterface;

    /**
     * @param array<string, mixed>|GotoOptions $options
     */
    public function goto(string $url, array|GotoOptions $options = []): ?ResponseInterface;

    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function click(string $selector, array|ClickOptions $options = []): self;

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

    public function evaluate(string $expression, mixed $arg = null): mixed;

    /**
     * @param array<string, mixed>|WaitForSelectorOptions $options
     */
    public function waitForSelector(string $selector, array|WaitForSelectorOptions $options = []): ?LocatorInterface;

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
     * @param array<string, mixed>|WaitForLoadStateOptions $options
     */
    public function waitForLoadState(string $state = 'load', array|WaitForLoadStateOptions $options = []): self;

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

    public function events(): PageEventHandlerInterface;

    public function route(string $url, callable $handler): void;

    public function unroute(string $url, ?callable $handler = null): void;

    public function handleDialog(string $dialogId, bool $accept, ?string $promptText = null): void;

    public function getPageIdForTransport(): string;

    public function waitForEvents(): void;

    /**
     * @param array<string, mixed>|WaitForPopupOptions $options
     */
    public function waitForPopup(callable $action, array|WaitForPopupOptions $options = []): self;

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
