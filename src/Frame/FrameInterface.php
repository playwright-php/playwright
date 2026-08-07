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

namespace Playwright\Frame;

use Playwright\Locator\LocatorInterface;
use Playwright\Network\ResponseInterface;
use Playwright\Page\Options\DragAndDropOptions;
use Playwright\Page\Options\GotoOptions;
use Playwright\Page\Options\ScriptTagOptions;
use Playwright\Page\Options\SetContentOptions;
use Playwright\Page\Options\StyleTagOptions;
use Playwright\Page\Options\WaitForFunctionOptions;
use Playwright\Page\Options\WaitForNavigationOptions;
use Playwright\Page\Options\WaitForUrlOptions;

interface FrameInterface
{
    /**
     * Create a locator within this frame.
     */
    public function locator(string $selector): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByAltText(string $text, array $options = []): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByLabel(string $text, array $options = []): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByPlaceholder(string $text, array $options = []): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByRole(string $role, array $options = []): LocatorInterface;

    public function getByTestId(string $testId): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByText(string $text, array $options = []): LocatorInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function getByTitle(string $text, array $options = []): LocatorInterface;

    /**
     * Create a nested frame locator from this frame.
     */
    public function frameLocator(string $selector): FrameLocatorInterface;

    /**
     * Get locator for the frame element itself (the <iframe> owner).
     */
    public function owner(): LocatorInterface;

    /**
     * The serialized HTML of the frame's own document, doctype included.
     *
     * Scoped to this frame, not to the page that embeds it.
     */
    public function content(): string;

    /**
     * @param array<string, mixed>|GotoOptions $options
     */
    public function goto(string $url, array|GotoOptions $options = []): ?ResponseInterface;

    /**
     * @param array<string, mixed>|SetContentOptions $options
     */
    public function setContent(string $html, array|SetContentOptions $options = []): self;

    /**
     * @param array<string, mixed>|WaitForFunctionOptions $options
     */
    public function waitForFunction(string $pageFunction, mixed $arg = null, array|WaitForFunctionOptions $options = []): self;

    /**
     * @param array<string, mixed>|WaitForUrlOptions $options
     */
    public function waitForURL(string $url, array|WaitForUrlOptions $options = []): self;

    /**
     * @param array<string, mixed>|WaitForNavigationOptions $options
     */
    public function waitForNavigation(array|WaitForNavigationOptions $options = []): ?ResponseInterface;

    /**
     * @param array<string, mixed>|DragAndDropOptions $options
     */
    public function dragAndDrop(string $source, string $target, array|DragAndDropOptions $options = []): self;

    /**
     * @param array{url?: string, path?: string, content?: string, type?: string}|ScriptTagOptions $options
     */
    public function addScriptTag(array|ScriptTagOptions $options = []): self;

    /**
     * @param array{url?: string, path?: string, content?: string}|StyleTagOptions $options
     */
    public function addStyleTag(array|StyleTagOptions $options = []): self;

    /**
     * Runs the expression in this frame's JavaScript context.
     *
     * The frame has its own global scope: what the page or a sibling frame
     * defined is not reachable here.
     */
    public function evaluate(string $expression, mixed $arg = null): mixed;

    /**
     * The frame's name attribute.
     */
    public function name(): string;

    /**
     * The frame document title.
     */
    public function title(): string;

    /**
     * The current URL loaded in this frame.
     */
    public function url(): string;

    /**
     * Whether the frame is detached from the DOM.
     */
    public function isDetached(): bool;

    /**
     * Wait for a particular load state in this frame.
     *
     * @param array<string, mixed> $options
     */
    public function waitForLoadState(string $state = 'load', array $options = []): self;

    /**
     * Get the parent frame, if any.
     */
    public function parentFrame(): ?FrameInterface;

    /**
     * Get the child frames of this frame.
     *
     * @return array<FrameInterface>
     */
    public function childFrames(): array;

    public function __toString(): string;
}
