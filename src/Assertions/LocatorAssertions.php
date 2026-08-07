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

use Playwright\Assertions\Internal\AbstractAssertions;
use Playwright\Assertions\Internal\AriaSnapshot;
use Playwright\Locator\LocatorInterface;
use Playwright\Tracing\TracingInterface;

final class LocatorAssertions extends AbstractAssertions implements LocatorAssertionsInterface
{
    /**
     * Resolves one accessible text of the element and compares it with the
     * expectation. `payload.kind` picks which text is resolved.
     */
    private const ACCESSIBLE_TEXT_JS = <<<'JS'
        (element, payload) => {
            const normalize = (value) => String(value ?? '').replace(/\s+/g, ' ').trim();
            const root = element.getRootNode();
            const scope = typeof root.getElementById === 'function' ? root : document;
            const attribute = (name) => normalize(element.getAttribute(name));
            const fromIdList = (list) => normalize(
                list
                    .split(' ')
                    .map(id => scope.getElementById(id))
                    .filter(target => target !== null)
                    .map(target => target.textContent)
                    .join(' ')
            );

            const name = () => {
                const label = attribute('aria-label');
                if (label !== '') return label;

                const labelledBy = fromIdList(attribute('aria-labelledby'));
                if (labelledBy !== '') return labelledBy;

                const labels = element.labels;
                if (labels && labels.length > 0) {
                    return normalize(Array.from(labels, target => target.textContent).join(' '));
                }

                return normalize(element.textContent);
            };

            const description = () => {
                const described = attribute('aria-description');
                if (described !== '') return described;

                const describedBy = fromIdList(attribute('aria-describedby'));
                if (describedBy !== '') return describedBy;

                return attribute('title');
            };

            const errorMessage = () => {
                const flag = attribute('aria-invalid').toLowerCase();
                const flagged = flag !== '' && flag !== 'false';
                const failsConstraints = !!element.validity && !element.validity.valid;
                if (!flagged && !failsConstraints) return '';

                return fromIdList(attribute('aria-errormessage'));
            };

            const resolved = {name, description, errorMessage}[payload.kind]();
            const expected = normalize(payload.expected);

            return payload.ignoreCase
                ? resolved.toLowerCase() === expected.toLowerCase()
                : resolved === expected;
        }
        JS;

    public function __construct(
        private readonly LocatorInterface $locator,
        ?TracingInterface $tracing = null,
    ) {
        parent::__construct($tracing);
    }

    public function not(): self
    {
        $this->negate();

        return $this;
    }

    public function withTimeout(int $timeoutMs): self
    {
        $this->setTimeout($timeoutMs);

        return $this;
    }

    public function withPollInterval(int $pollIntervalMs): self
    {
        $this->setPollInterval($pollIntervalMs);

        return $this;
    }

    public function toBeAttached(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => $this->locator->isAttached(),
            'toBeAttached',
            $options,
            'Expected locator to be attached.',
            'Expected locator to be detached.',
        );
    }

    public function toBeEditable(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => $this->locator->isEditable(),
            'toBeEditable',
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
            'toBeInViewport',
            $options,
            'Expected locator to be in the viewport.',
            'Expected locator not to be in the viewport.',
        );
    }

    public function toBeVisible(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => $this->locator->isVisible(),
            'toBeVisible',
            $options,
            'Expected locator to be visible.',
            'Expected locator to be hidden.',
        );
    }

    public function toBeHidden(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => !$this->locator->isVisible(),
            'toBeHidden',
            $options,
            'Expected locator to be hidden.',
            'Expected locator to be visible.',
        );
    }

    /**
     * @param string|array<string> $expected
     */
    public function toHaveText(string|array $expected, ?AssertionOptions $options = null): self
    {
        $useInner = $options?->useInnerText;
        if (!is_bool($useInner)) {
            $useInner = false;
        }

        $predicate = function () use ($expected, $useInner) {
            $actual = \is_array($expected)
                ? ($useInner ? $this->locator->allInnerTexts() : $this->locator->allTextContents())
                : ($useInner ? $this->locator->innerText() : $this->locator->textContent());

            return $actual === $expected;
        };

        $this->assertCondition(
            $predicate,
            'toHaveText',
            $options,
            'Expected locator to have text.',
            'Expected locator not to have text.',
            $expected,
            fn (): mixed => $useInner ? $this->locator->innerText() : $this->locator->textContent(),
        );

        return $this;
    }

    public function toContainText(string $expected, ?AssertionOptions $options = null): self
    {
        $this->assertCondition(
            fn (): bool => str_contains($this->locator->textContent() ?? '', $expected),
            'toContainText',
            $options,
            sprintf('Expected locator text to contain %s.', json_encode($expected)),
            sprintf('Expected locator text not to contain %s.', json_encode($expected)),
            $expected,
            fn (): ?string => $this->locator->textContent(),
        );

        return $this;
    }

    public function toHaveExactText(string $expected, ?AssertionOptions $options = null): self
    {
        $this->assertCondition(
            fn (): bool => ($this->locator->textContent() ?? '') === $expected,
            'toHaveExactText',
            $options,
            'Expected locator to have exact text.',
            'Expected locator not to have exact text.',
            $expected,
            fn (): ?string => $this->locator->textContent(),
        );

        return $this;
    }

    public function toHaveValue(string $expected, ?AssertionOptions $options = null): self
    {
        $this->assertCondition(
            fn (): bool => $this->locator->inputValue() === $expected,
            'toHaveValue',
            $options,
            'Expected locator to have value.',
            'Expected locator not to have value.',
            $expected,
            fn (): string => $this->locator->inputValue(),
        );

        return $this;
    }

    public function toHaveAttribute(string $name, string $expected, ?AssertionOptions $options = null): self
    {
        $this->assertCondition(
            fn (): bool => $this->locator->getAttribute($name) === $expected,
            'toHaveAttribute',
            $options,
            sprintf('Expected locator attribute "%s" to match.', $name),
            sprintf('Expected locator attribute "%s" not to match.', $name),
            $expected,
            fn (): ?string => $this->locator->getAttribute($name),
        );

        return $this;
    }

    public function toBeChecked(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => $this->locator->isChecked(),
            'toBeChecked',
            $options,
            'Expected locator to be checked.',
            'Expected locator not to be checked.',
        );
    }

    public function toBeEnabled(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => $this->locator->isEnabled(),
            'toBeEnabled',
            $options,
            'Expected locator to be enabled.',
            'Expected locator not to be enabled.',
        );
    }

    public function toBeDisabled(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => !$this->locator->isEnabled(),
            'toBeDisabled',
            $options,
            'Expected locator to be disabled.',
            'Expected locator not to be disabled.',
        );
    }

    public function toHaveCSS(string $name, string $expected, ?AssertionOptions $options = null): self
    {
        $actual = fn (): mixed => $this->locator->evaluate(
            '(element, property) => window.getComputedStyle(element).getPropertyValue(property)',
            $name,
        );

        $this->assertCondition(
            fn (): bool => $actual() === $expected,
            'toHaveCSS',
            $options,
            sprintf('Expected locator CSS property "%s" to match.', $name),
            sprintf('Expected locator CSS property "%s" not to match.', $name),
            $expected,
            $actual,
        );

        return $this;
    }

    public function toHaveId(string $expected, ?AssertionOptions $options = null): self
    {
        return $this->toHaveAttribute('id', $expected, $options);
    }

    /**
     * @param string|string[] $expected
     */
    public function toHaveClass(string|array $expected, ?AssertionOptions $options = null): self
    {
        $expectedClasses = self::classTokens(is_array($expected) ? implode(' ', $expected) : $expected);

        $this->assertCondition(
            function () use ($expectedClasses): bool {
                $actual = $this->locator->getAttribute('class');

                return null !== $actual && self::classTokens($actual) === $expectedClasses;
            },
            'toHaveClass',
            $options,
            'Expected locator class list to match.',
            'Expected locator class list not to match.',
            implode(' ', $expectedClasses),
            fn (): ?string => $this->locator->getAttribute('class'),
        );

        return $this;
    }

    public function toBeEmpty(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => true === $this->locator->evaluate(<<<'JS'
                (element) => 'value' in element
                    ? element.value === ''
                    : (element.textContent ?? '') === ''
                JS),
            'toBeEmpty',
            $options,
            'Expected locator to be empty.',
            'Expected locator not to be empty.',
        );
    }

    public function toBeFocused(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => true === $this->locator->evaluate('(element) => document.activeElement === element'),
            'toBeFocused',
            $options,
            'Expected locator to be focused.',
            'Expected locator not to be focused.',
        );
    }

    public function toHaveFocus(?AssertionOptions $options = null): self
    {
        return $this->assertState(
            fn (): bool => true === $this->locator->evaluate('(element) => document.activeElement === element'),
            'toHaveFocus',
            $options,
            'Expected locator to have focus.',
            'Expected locator not to have focus.',
        );
    }

    public function toHaveCount(int $expected, ?AssertionOptions $options = null): self
    {
        $this->assertCondition(
            fn (): bool => $this->locator->count() === $expected,
            'toHaveCount',
            $options,
            'Expected locator count to match.',
            'Expected locator count not to match.',
            $expected,
            fn (): int => $this->locator->count(),
        );

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
            'toHaveJSProperty',
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
            'toHaveValues',
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
            'toHaveRole',
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
            'toContainClass',
            $options,
            sprintf('Expected locator to contain class "%s".', $expected),
            sprintf('Expected locator not to contain class "%s".', $expected),
        );
    }

    public function toHaveAccessibleName(string $expected, ?AssertionOptions $options = null): self
    {
        return $this->assertAccessibleText(
            'toHaveAccessibleName',
            'name',
            $expected,
            $options,
            sprintf('Expected locator to have accessible name "%s".', $expected),
            sprintf('Expected locator not to have accessible name "%s".', $expected),
        );
    }

    public function toHaveAccessibleDescription(string $expected, ?AssertionOptions $options = null): self
    {
        return $this->assertAccessibleText(
            'toHaveAccessibleDescription',
            'description',
            $expected,
            $options,
            sprintf('Expected locator to have accessible description "%s".', $expected),
            sprintf('Expected locator not to have accessible description "%s".', $expected),
        );
    }

    public function toHaveAccessibleErrorMessage(string $expected, ?AssertionOptions $options = null): self
    {
        return $this->assertAccessibleText(
            'toHaveAccessibleErrorMessage',
            'errorMessage',
            $expected,
            $options,
            sprintf('Expected locator to have accessible error message "%s".', $expected),
            sprintf('Expected locator not to have accessible error message "%s".', $expected),
        );
    }

    public function toMatchAriaSnapshot(string $expected, ?AssertionOptions $options = null): self
    {
        $normalized = AriaSnapshot::normalize($expected);

        return $this->assertState(
            fn (): bool => AriaSnapshot::normalize($this->locator->ariaSnapshot()) === $normalized,
            'toMatchAriaSnapshot',
            $options,
            'Expected locator to match the ARIA snapshot.',
            'Expected locator not to match the ARIA snapshot.',
        );
    }

    private function assertAccessibleText(string $matcher, string $kind, string $expected, ?AssertionOptions $options, string $expectedMessage, string $negatedMessage): self
    {
        $payload = [
            'kind' => $kind,
            'expected' => $expected,
            'ignoreCase' => true === $options?->ignoreCase,
        ];

        return $this->assertState(
            fn (): bool => true === $this->locator->evaluate(self::ACCESSIBLE_TEXT_JS, $payload),
            $matcher,
            $options,
            $expectedMessage,
            $negatedMessage,
        );
    }

    /**
     * @param callable(): bool $predicate
     * @param callable(): bool $predicate
     */
    private function assertState(callable $predicate, string $matcher, ?AssertionOptions $options, string $expectedMessage, string $negatedMessage): self
    {
        $this->assertCondition($predicate, $matcher, $options, $expectedMessage, $negatedMessage);

        return $this;
    }

    /**
     * @return list<string>
     */
    private static function classTokens(string $classAttribute): array
    {
        $tokens = preg_split('/\s+/', trim($classAttribute), -1, PREG_SPLIT_NO_EMPTY);

        return false === $tokens ? [] : $tokens;
    }

    protected function subjectName(): string
    {
        return $this->locator->getSelector();
    }
}
