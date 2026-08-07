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

namespace Playwright\Assertions;

use Playwright\Assertions\Failure\AssertionException;
use Playwright\Assertions\Internal\Waiter;
use Playwright\Locator\LocatorInterface;

final class LocatorAssertions implements LocatorAssertionsInterface
{
    private bool $negated = false;

    public function __construct(private LocatorInterface $locator)
    {
    }

    public function not(): self
    {
        $this->negated = !$this->negated;

        return $this;
    }

    public function toBeAttached(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => $this->locator->isAttached(),
            $options,
            'Expected locator to be attached.',
            'Expected locator to be detached.',
        );
    }

    public function toBeEditable(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => $this->locator->isEditable(),
            $options,
            'Expected locator to be editable.',
            'Expected locator not to be editable.',
        );
    }

    public function toBeInViewport(?AssertionOptions $options = null): self
    {
        $ratio = $options?->ratio;
        if (!is_float($ratio)) {
            $ratio = 0.0;
        }
        if ($ratio < 0 || $ratio > 1) {
            throw new \InvalidArgumentException('Viewport ratio must be between 0 and 1.');
        }

        return $this->assertState(
            fn (): bool => true === $this->locator->evaluate(<<<'JS'
                (element, requiredRatio) => {
                    const rect = element.getBoundingClientRect();
                    const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                    const visibleWidth = Math.max(0, Math.min(rect.right, viewportWidth) - Math.max(rect.left, 0));
                    const visibleHeight = Math.max(0, Math.min(rect.bottom, viewportHeight) - Math.max(rect.top, 0));
                    const visibleArea = visibleWidth * visibleHeight;
                    const elementArea = rect.width * rect.height;

                    // A ratio of 0 means "any non-empty intersection", so the
                    // visible area has to be positive on its own: dividing it by
                    // the element area would satisfy `>= 0` off-screen too.
                    return elementArea > 0 && visibleArea > 0 && visibleArea / elementArea >= requiredRatio;
                }
                JS, $ratio),
            $options,
            'Expected locator to be in the viewport.',
            'Expected locator not to be in the viewport.',
        );
    }

    public function toBeVisible(?AssertionOptions $options = null): self
    {
        $timeout = $options?->timeoutMs;
        if (!is_int($timeout)) {
            $timeout = Waiter::DEFAULT_TIMEOUT_MS;
        }
        $interval = $options?->intervalMs;
        if (!is_int($interval)) {
            $interval = 50;
        }

        try {
            Waiter::eventually(fn () => $this->locator->isVisible(), $timeout, $interval);
        } catch (\Throwable $e) {
            $ok = false;
            if ($this->negated) {
                $ok = true;
                $this->negated = false;
            }
            if (!$ok) {
                $msg = $options?->message;
                if (null === $msg) {
                    $msg = 'Expected locator to be visible.';
                }
                throw new AssertionException($msg);
            }

            return $this;
        }

        $ok = true;
        if ($this->negated) {
            $ok = false;
            $this->negated = false;
        }
        if (!$ok) {
            $msg = $options?->message;
            if (null === $msg) {
                $msg = 'Expected locator to be hidden.';
            }
            throw new AssertionException($msg);
        }

        return $this;
    }

    public function toBeHidden(?AssertionOptions $options = null): self
    {
        return $this->not()->toBeVisible($options);
    }

    /**
     * @param string|array<string> $expected
     */
    public function toHaveText(string|array $expected, ?AssertionOptions $options = null): self
    {
        $timeout = $options?->timeoutMs;
        if (!is_int($timeout)) {
            $timeout = Waiter::DEFAULT_TIMEOUT_MS;
        }
        $useInner = $options?->useInnerText;
        if (!is_bool($useInner)) {
            $useInner = false;
        }
        $interval = $options?->intervalMs;
        if (!is_int($interval)) {
            $interval = 50;
        }

        $predicate = function () use ($expected, $useInner) {
            $actual = \is_array($expected)
                ? ($useInner ? $this->locator->allInnerTexts() : $this->locator->allTextContents())
                : ($useInner ? $this->locator->innerText() : $this->locator->textContent());

            return $actual === $expected;
        };

        $ok = true;
        try {
            Waiter::eventually($predicate, $timeout, $interval);
        } catch (\Throwable) {
            $ok = false;
        }

        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }

        if (!$ok) {
            $msg = $options?->message;
            if (null === $msg) {
                $msg = 'Expected locator to have text.';
            }
            throw new AssertionException($msg, actual: $useInner ? $this->locator->innerText() : $this->locator->textContent(), expected: $expected);
        }

        return $this;
    }

    public function toHaveCount(int $expected, ?AssertionOptions $options = null): self
    {
        $timeout = $options?->timeoutMs;
        if (!is_int($timeout)) {
            $timeout = Waiter::DEFAULT_TIMEOUT_MS;
        }
        $interval = $options?->intervalMs;
        if (!is_int($interval)) {
            $interval = 50;
        }

        $ok = true;
        try {
            Waiter::eventually(fn () => $this->locator->count() === $expected, $timeout, $interval);
        } catch (\Throwable) {
            $ok = false;
        }

        if ($this->negated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            throw new AssertionException('Expected locator count to match.', actual: $this->locator->count(), expected: $expected);
        }

        return $this;
    }

    public function toHaveJSProperty(string $name, mixed $expected, ?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => true === $this->locator->evaluate(<<<'JS'
                (element, payload) => {
                    const equal = (actual, expected) => {
                        if (Object.is(actual, expected)) {
                            return true;
                        }
                        if (actual === null || expected === null || typeof actual !== 'object' || typeof expected !== 'object') {
                            return false;
                        }
                        if (Array.isArray(actual) !== Array.isArray(expected)) {
                            return false;
                        }
                        const actualKeys = Object.keys(actual);
                        const expectedKeys = Object.keys(expected);
                        return actualKeys.length === expectedKeys.length
                            && actualKeys.every(key => Object.prototype.hasOwnProperty.call(expected, key) && equal(actual[key], expected[key]));
                    };

                    return equal(element[payload.name], payload.expected);
                }
                JS, ['name' => $name, 'expected' => $expected]),
            $options,
            sprintf('Expected locator JavaScript property "%s" to match.', $name),
            sprintf('Expected locator JavaScript property "%s" not to match.', $name),
        );
    }

    /**
     * @param array<string>|string $expected
     */
    public function toHaveValues(array|string $expected, ?AssertionOptions $options = null): self
    {
        $expected = is_array($expected) ? array_values($expected) : [$expected];

        return $this->assertState(
            fn (): bool => $this->locator->evaluate(<<<'JS'
                (element) => element instanceof HTMLSelectElement
                    ? Array.from(element.selectedOptions, option => option.value)
                    : null
                JS) === $expected,
            $options,
            'Expected locator to have selected values.',
            'Expected locator not to have selected values.',
        );
    }

    public function toHaveRole(string $role, ?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => $this->locator->evaluate(<<<'JS'
                (element) => {
                    const explicitRole = element.getAttribute('role');
                    if (explicitRole) {
                        return explicitRole.trim().split(/\s+/, 1)[0];
                    }

                    const tagName = element.localName;
                    if (tagName === 'a' || tagName === 'area') return element.hasAttribute('href') ? 'link' : null;
                    if (tagName === 'button' || tagName === 'summary') return 'button';
                    if (tagName === 'article') return 'article';
                    if (tagName === 'aside') return 'complementary';
                    if (tagName === 'details') return 'group';
                    if (tagName === 'dialog') return 'dialog';
                    if (tagName === 'dl' || tagName === 'ul' || tagName === 'ol' || tagName === 'menu') return 'list';
                    if (tagName === 'dt') return 'term';
                    if (tagName === 'dd') return 'definition';
                    if (tagName === 'fieldset') return 'group';
                    if (tagName === 'figure') return 'figure';
                    if (/^h[1-6]$/.test(tagName)) return 'heading';
                    if (tagName === 'hr') return 'separator';
                    if (tagName === 'img') return element.getAttribute('alt') === '' ? 'presentation' : 'img';
                    if (tagName === 'li') return 'listitem';
                    if (tagName === 'main') return 'main';
                    if (tagName === 'nav') return 'navigation';
                    if (tagName === 'option') return 'option';
                    if (tagName === 'output') return 'status';
                    if (tagName === 'progress') return 'progressbar';
                    if (tagName === 'select') return element.multiple || element.size > 1 ? 'listbox' : 'combobox';
                    if (tagName === 'table') return 'table';
                    if (tagName === 'tbody' || tagName === 'thead' || tagName === 'tfoot') return 'rowgroup';
                    if (tagName === 'tr') return 'row';
                    if (tagName === 'td') return 'cell';
                    if (tagName === 'th') return element.scope === 'row' || element.scope === 'rowgroup' ? 'rowheader' : 'columnheader';
                    if (tagName === 'textarea') return 'textbox';
                    if (tagName !== 'input') return null;

                    switch (element.type) {
                        case 'button': case 'image': case 'reset': case 'submit': return 'button';
                        case 'checkbox': return 'checkbox';
                        case 'radio': return 'radio';
                        case 'range': return 'slider';
                        case 'number': return 'spinbutton';
                        case 'search': return 'searchbox';
                        case 'email': case 'tel': case 'text': case 'url': case 'password': return 'textbox';
                        default: return null;
                    }
                }
                JS) === $role,
            $options,
            sprintf('Expected locator to have role "%s".', $role),
            sprintf('Expected locator not to have role "%s".', $role),
        );
    }

    public function toContainClass(string $expected, ?AssertionOptions $options = null): self
    {
        $classes = preg_split('/\s+/', trim($expected), -1, PREG_SPLIT_NO_EMPTY);
        if (false === $classes || [] === $classes) {
            throw new \InvalidArgumentException('Expected class must contain at least one class token.');
        }

        return $this->assertState(
            fn (): bool => true === $this->locator->evaluate(
                '(element, expectedClasses) => expectedClasses.every(className => element.classList.contains(className))',
                $classes
            ),
            $options,
            sprintf('Expected locator to contain class "%s".', $expected),
            sprintf('Expected locator not to contain class "%s".', $expected),
        );
    }

    /**
     * @param callable(): bool $predicate
     */
    private function assertState(callable $predicate, ?AssertionOptions $options, string $expectedMessage, string $negatedMessage): self
    {
        $timeout = $options?->timeoutMs;
        if (!is_int($timeout)) {
            $timeout = Waiter::DEFAULT_TIMEOUT_MS;
        }
        $interval = $options?->intervalMs;
        if (!is_int($interval)) {
            $interval = 50;
        }

        $ok = true;
        try {
            Waiter::eventually($predicate, $timeout, $interval);
        } catch (\Throwable) {
            $ok = false;
        }

        $wasNegated = $this->negated;
        if ($wasNegated) {
            $ok = !$ok;
            $this->negated = false;
        }
        if (!$ok) {
            $message = $options instanceof AssertionOptions ? $options->message : null;
            throw new AssertionException($message ?? ($wasNegated ? $negatedMessage : $expectedMessage));
        }

        return $this;
    }
}
