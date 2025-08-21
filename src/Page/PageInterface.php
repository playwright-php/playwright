<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Page;

use PlaywrightPHP\Browser\BrowserContextInterface;
use PlaywrightPHP\FrameLocator\FrameLocatorInterface;
use PlaywrightPHP\Input\KeyboardInterface;
use PlaywrightPHP\Input\MouseInterface;
use PlaywrightPHP\Locator\LocatorInterface;
use PlaywrightPHP\Network\ResponseInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface PageInterface
{
    public function locator(string $selector): LocatorInterface;

    public function goto(string $url, array $options = []): ?ResponseInterface;

    public function click(string $selector, array $options = []): self;

    public function type(string $selector, string $text, array $options = []): self;

    public function screenshot(?string $path = null, array $options = []): string;

    public function content(): ?string;

    public function evaluate(string $expression, mixed $arg = null): mixed;

    public function waitForSelector(string $selector, array $options = []): ?LocatorInterface;

    public function close(): void;

    public function bringToFront(): self;

    public function context(): BrowserContextInterface;

    /**
     * @param array<string>|null $urls
     *
     * @return array<array{name: string, value: string, domain: string, path: string, expires: int, httpOnly: bool, secure: bool, sameSite: 'Strict'|'Lax'|'None'}>
     */
    public function cookies(?array $urls = null): array;

    public function goBack(array $options = []): self;

    public function goForward(array $options = []): self;

    public function reload(array $options = []): self;

    public function setContent(string $html, array $options = []): self;

    public function url(): string;

    public function title(): string;

    /**
     * @return array{width: int, height: int}|null
     */
    public function viewportSize(): ?array;

    public function setViewportSize(int $width, int $height): self;

    public function waitForLoadState(string $state = 'load', array $options = []): self;

    /**
     * @param string|callable $url
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
     * Set files to an input element with type="file".
     *
     * @param string        $selector The input selector
     * @param array<string> $files    Array of file paths to set
     * @param array         $options  Additional options
     */
    public function setInputFiles(string $selector, array $files, array $options = []): self;
}
