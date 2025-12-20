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

namespace Playwright\Locator;

use Playwright\Frame\FrameLocatorInterface;
use Playwright\Locator\Options\CheckOptions;
use Playwright\Locator\Options\ClearOptions;
use Playwright\Locator\Options\ClickOptions;
use Playwright\Locator\Options\DblClickOptions;
use Playwright\Locator\Options\DragToOptions;
use Playwright\Locator\Options\FillOptions;
use Playwright\Locator\Options\FilterOptions;
use Playwright\Locator\Options\GetAttributeOptions;
use Playwright\Locator\Options\GetByOptions;
use Playwright\Locator\Options\GetByRoleOptions;
use Playwright\Locator\Options\HoverOptions;
use Playwright\Locator\Options\LocatorScreenshotOptions;
use Playwright\Locator\Options\PressOptions;
use Playwright\Locator\Options\SelectOptionOptions;
use Playwright\Locator\Options\SetInputFilesOptions;
use Playwright\Locator\Options\TextContentOptions;
use Playwright\Locator\Options\TypeOptions;
use Playwright\Locator\Options\UncheckOptions;
use Playwright\Locator\Options\WaitForOptions;

interface LocatorInterface
{
    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function click(array|ClickOptions $options = []): void;

    /**
     * @param array<string, mixed>|FillOptions $options
     */
    public function fill(string $value, array|FillOptions $options = []): void;

    /**
     * @param array<string, mixed>|TypeOptions $options
     */
    public function type(string $value, array|TypeOptions $options = []): void;

    /**
     * @param array<string, mixed>|PressOptions $options
     */
    public function press(string $key, array|PressOptions $options = []): void;

    /**
     * @param array<string, mixed>|CheckOptions $options
     */
    public function check(array|CheckOptions $options = []): void;

    /**
     * @param array<string, mixed>|UncheckOptions $options
     */
    public function uncheck(array|UncheckOptions $options = []): void;

    /**
     * @param array<string, mixed>|HoverOptions $options
     */
    public function hover(array|HoverOptions $options = []): void;

    /**
     * Drag this element to target element.
     *
     * @param array<string, mixed>|DragToOptions $options
     */
    public function dragTo(LocatorInterface $target, array|DragToOptions $options = []): void;

    /**
     * @param array<string, mixed>|DblClickOptions $options
     */
    public function dblclick(array|DblClickOptions $options = []): void;

    /**
     * @param array<string, mixed>|ClearOptions $options
     */
    public function clear(array|ClearOptions $options = []): void;

    public function focus(): void;

    public function blur(): void;

    /**
     * @param array<string, mixed>|LocatorScreenshotOptions $options
     */
    public function screenshot(?string $path = null, array|LocatorScreenshotOptions $options = []): ?string;

    /**
     * @return array<string>
     */
    public function allInnerTexts(): array;

    /**
     * @return array<string>
     */
    public function allTextContents(): array;

    public function innerHTML(): string;

    public function innerText(): string;

    public function inputValue(): string;

    public function isAttached(): bool;

    public function isChecked(): bool;

    public function isDisabled(): bool;

    public function isEditable(): bool;

    public function isEmpty(): bool;

    public function isEnabled(): bool;

    public function isHidden(): bool;

    public function isVisible(): bool;

    public function locator(string $selector): self;

    /**
     * @param array<string, mixed>|GetByOptions $options
     */
    public function getByAltText(string $text, array|GetByOptions $options = []): self;

    /**
     * @param array<string, mixed>|GetByOptions $options
     */
    public function getByLabel(string $text, array|GetByOptions $options = []): self;

    /**
     * @param array<string, mixed>|GetByOptions $options
     */
    public function getByPlaceholder(string $text, array|GetByOptions $options = []): self;

    /**
     * @param array<string, mixed>|GetByRoleOptions $options
     */
    public function getByRole(string $role, array|GetByRoleOptions $options = []): self;

    public function getByTestId(string $testId): self;

    /**
     * @param array<string, mixed>|GetByOptions $options
     */
    public function getByText(string $text, array|GetByOptions $options = []): self;

    /**
     * @param array<string, mixed>|GetByOptions $options
     */
    public function getByTitle(string $text, array|GetByOptions $options = []): self;

    /**
     * @param array<string, mixed>|WaitForOptions $options
     */
    public function waitFor(array|WaitForOptions $options = []): void;

    /**
     * @param string|array<string>                     $values
     * @param array<string, mixed>|SelectOptionOptions $options
     *
     * @return array<string>
     */
    public function selectOption(string|array $values, array|SelectOptionOptions $options = []): array;

    /**
     * @param string|array<string>                      $files
     * @param array<string, mixed>|SetInputFilesOptions $options
     */
    public function setInputFiles(string|array $files, array|SetInputFilesOptions $options = []): void;

    /**
     * @param array<string, mixed>|TextContentOptions $options
     */
    public function textContent(array|TextContentOptions $options = []): ?string;

    /**
     * @param array<string, mixed>|GetAttributeOptions $options
     */
    public function getAttribute(string $name, array|GetAttributeOptions $options = []): ?string;

    public function count(): int;

    /**
     * @return array<LocatorInterface>
     */
    public function all(): array;

    public function first(): self;

    public function last(): self;

    public function nth(int $index): self;

    public function evaluate(string $expression, mixed $arg = null): mixed;

    public function frameLocator(string $selector): FrameLocatorInterface;

    public function getSelector(): string;

    /**
     * @param array<string, mixed>|FilterOptions $options
     */
    public function filter(array|FilterOptions $options = []): self;

    public function and(LocatorInterface $locator): self;

    public function or(LocatorInterface $locator): self;

    public function describe(string $description): self;

    public function contentFrame(): FrameLocatorInterface;
}
