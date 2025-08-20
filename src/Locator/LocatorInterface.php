<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Locator;

use PlaywrightPHP\FrameLocator\FrameLocatorInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface LocatorInterface
{
    public function click(array $options = []): void;

    public function fill(string $value, array $options = []): void;

    public function type(string $value, array $options = []): void;

    public function press(string $key, array $options = []): void;

    public function check(array $options = []): void;

    public function uncheck(array $options = []): void;

    public function hover(array $options = []): void;

    public function dblclick(array $options = []): void;

    public function clear(array $options = []): void;

    public function focus(): void;

    public function blur(): void;

    public function screenshot(?string $path = null, array $options = []): ?string;

    public function allInnerTexts(): array;

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

    public function waitFor(array $options = []): void;

    /**
     * @param string|array|null $values
     */
    public function selectOption($values, array $options = []): array;

    /**
     * @param string|array $files
     */
    public function setInputFiles($files, array $options = []): void;

    public function textContent(array $options = []): ?string;

    public function getAttribute(string $name, array $options = []): ?string;

    public function count(): int;

    public function all(): array;

    public function first(): self;

    public function last(): self;

    public function nth(int $index): self;

    public function evaluate(string $expression, mixed $arg = null): mixed;

    public function frameLocator(string $selector): FrameLocatorInterface;
}
