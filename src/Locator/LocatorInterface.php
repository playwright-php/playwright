<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Locator;

use PlaywrightPHP\Frame\FrameLocatorInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface LocatorInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function click(array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function fill(string $value, array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function type(string $value, array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function press(string $key, array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function check(array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function uncheck(array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function hover(array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function dblclick(array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function clear(array $options = []): void;

    public function focus(): void;

    public function blur(): void;

    /**
     * @param array<string, mixed> $options
     */
    public function screenshot(?string $path = null, array $options = []): ?string;

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
     * @param array<string, mixed> $options
     */
    public function waitFor(array $options = []): void;

    /**
     * @param string|array<string> $values
     * @param array<string, mixed> $options
     *
     * @return array<string>
     */
    public function selectOption(string|array $values, array $options = []): array;

    /**
     * @param string|array<string> $files
     * @param array<string, mixed> $options
     */
    public function setInputFiles(string|array $files, array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function textContent(array $options = []): ?string;

    /**
     * @param array<string, mixed> $options
     */
    public function getAttribute(string $name, array $options = []): ?string;

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
}
