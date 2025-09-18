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

use Playwright\Browser\BrowserContextInterface;
use Playwright\Frame\FrameInterface;
use Playwright\Frame\FrameLocatorInterface;
use Playwright\Input\KeyboardInterface;
use Playwright\Input\MouseInterface;
use Playwright\Locator\LocatorInterface;
use Playwright\Network\ResponseInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface PageInterface
{
    public function locator(string $selector): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function goto(string $url, array $options = []): ?ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function click(string $selector, array $options = []): self;

    /**
     * @param array<string, mixed> $options
     */
    public function altClick(string $selector, array $options = []): self;

    /**
     * @param array<string, mixed> $options
     */
    public function controlClick(string $selector, array $options = []): self;

    /**
     * @param array<string, mixed> $options
     */
    public function shiftClick(string $selector, array $options = []): self;

    /**
     * @param array<string, mixed> $options
     */
    public function type(string $selector, string $text, array $options = []): self;

    /**
     * @param array<string, mixed> $options
     */
    public function screenshot(?string $path = null, array $options = []): string;

    public function content(): ?string;

    public function evaluate(string $expression, mixed $arg = null): mixed;

    /**
     * @param array<string, mixed> $options
     */
    public function waitForSelector(string $selector, array $options = []): ?LocatorInterface;

    public function close(): void;

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
     * @param array<string, mixed> $options
     */
    public function goBack(array $options = []): self;

    /**
     * @param array<string, mixed> $options
     */
    public function goForward(array $options = []): self;

    /**
     * @param array<string, mixed> $options
     */
    public function reload(array $options = []): self;

    /**
     * @param array<string, mixed> $options
     */
    public function setContent(string $html, array $options = []): self;

    public function url(): string;

    public function title(): string;

    /**
     * @return array{width: int, height: int}|null
     */
    public function viewportSize(): ?array;

    public function setViewportSize(int $width, int $height): self;

    /**
     * @param array<string, mixed> $options
     */
    public function waitForLoadState(string $state = 'load', array $options = []): self;

    /**
     * @param string|callable      $url
     * @param array<string, mixed> $options
     */
    public function waitForURL($url, array $options = []): self;

    /**
     * @param array{url?: string, path?: string, content?: string, type?: string} $options
     */
    public function addScriptTag(array $options): self;

    /**
     * @param array{url?: string, path?: string, content?: string} $options
     */
    public function addStyleTag(array $options): self;

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
     * @param array<string, mixed> $options
     */
    public function waitForPopup(callable $action, array $options = []): self;

    /**
     * Set files to an input element with type="file".
     *
     * @param string               $selector The input selector
     * @param array<string>        $files    Array of file paths to set
     * @param array<string, mixed> $options  Additional options
     */
    public function setInputFiles(string $selector, array $files, array $options = []): self;

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
     * @param array{name?: string, url?: string, urlRegex?: string} $options
     */
    public function frame(array $options): ?FrameInterface;
}
