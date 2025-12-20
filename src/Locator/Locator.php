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

use Playwright\Exception\PlaywrightException;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Exception\TimeoutException;
use Playwright\Frame\FrameLocator;
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
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Locator implements \Stringable, LocatorInterface
{
    private SelectorChain $selectorChain;

    private LoggerInterface $logger;

    /**
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $pageId,
        string|SelectorChain $selector,
        private readonly ?string $frameSelector = null,
        ?LoggerInterface $logger = null,
        array $options = [],
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->options = $options;
        if ($selector instanceof SelectorChain) {
            $this->selectorChain = $selector;
        } else {
            $this->selectorChain = new SelectorChain($selector);
        }
    }

    public function __toString(): string
    {
        return 'Locator(selector="'.$this->selectorChain.'")';
    }

    public function getSelector(): string
    {
        return (string) $this->selectorChain;
    }

    /**
     * Expose the options provided at construction time.
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, mixed>|ClickOptions $options
     */
    public function click(array|ClickOptions $options = []): void
    {
        $options = ClickOptions::from($options);
        $this->waitForActionable();
        $this->sendCommand('locator.click', ['options' => $options->toArray()]);
    }

    /**
     * @param array<string, mixed>|DblClickOptions $options
     */
    public function dblclick(array|DblClickOptions $options = []): void
    {
        $options = DblClickOptions::from($options);
        $this->sendCommand('locator.dblclick', ['options' => $options->toArray()]);
    }

    /**
     * @param array<string, mixed>|ClearOptions $options
     */
    public function clear(array|ClearOptions $options = []): void
    {
        $options = ClearOptions::from($options);
        $this->sendCommand('locator.clear', ['options' => $options->toArray()]);
    }

    public function focus(): void
    {
        $this->sendCommand('locator.focus');
    }

    public function blur(): void
    {
        $this->sendCommand('locator.blur');
    }

    /**
     * @param array<string, mixed>|LocatorScreenshotOptions $options
     */
    public function screenshot(?string $path = null, array|LocatorScreenshotOptions $options = []): ?string
    {
        $options = LocatorScreenshotOptions::from($options);
        if ($path) {
            $options->path = $path;
        }
        $response = $this->sendCommand('locator.screenshot', ['options' => $options->toArray()]);

        if ($path) {
            return null;
        }
        $binary = $response['binary'] ?? null;

        return is_string($binary) ? $binary : null;
    }

    /**
     * @return array<string>
     */
    public function allInnerTexts(): array
    {
        $response = $this->sendCommand('locator.allInnerTexts');
        $value = $response['value'] ?? [];
        if (!is_array($value)) {
            return [];
        }

        return array_filter($value, 'is_string');
    }

    /**
     * @return array<string>
     */
    public function allTextContents(): array
    {
        $response = $this->sendCommand('locator.allTextContents');
        $value = $response['value'] ?? [];
        if (!is_array($value)) {
            return [];
        }

        return array_filter($value, 'is_string');
    }

    public function innerHTML(): string
    {
        $response = $this->sendCommand('locator.innerHTML');
        $value = $response['value'];
        if (!is_string($value)) {
            throw new ProtocolErrorException('Invalid innerHTML response', 0);
        }

        return $value;
    }

    public function innerText(): string
    {
        $response = $this->sendCommand('locator.innerText');
        $value = $response['value'];
        if (!is_string($value)) {
            throw new ProtocolErrorException('Invalid innerText response', 0);
        }

        return $value;
    }

    public function inputValue(): string
    {
        $response = $this->sendCommand('locator.inputValue');
        $value = $response['value'];
        if (!is_string($value)) {
            throw new ProtocolErrorException('Invalid inputValue response', 0);
        }

        return $value;
    }

    public function isAttached(): bool
    {
        $response = $this->sendCommand('locator.isAttached');

        return true === $response['value'];
    }

    public function isChecked(): bool
    {
        $response = $this->sendCommand('locator.isChecked');

        return true === $response['value'];
    }

    public function isDisabled(): bool
    {
        $response = $this->sendCommand('locator.isDisabled');

        return true === $response['value'];
    }

    public function isEditable(): bool
    {
        $response = $this->sendCommand('locator.isEditable');

        return true === $response['value'];
    }

    public function isEmpty(): bool
    {
        $response = $this->sendCommand('locator.isEmpty');

        return true === $response['value'];
    }

    public function isEnabled(): bool
    {
        $response = $this->sendCommand('locator.isEnabled');

        return true === $response['value'];
    }

    public function isHidden(): bool
    {
        $response = $this->sendCommand('locator.isHidden');

        return true === $response['value'];
    }

    public function isVisible(): bool
    {
        $response = $this->sendCommand('locator.isVisible');

        return true === $response['value'];
    }

    public function locator(string $selector): self
    {
        $newSelectorChain = clone $this->selectorChain;
        $newSelectorChain->append($selector);

        return new Locator($this->transport, $this->pageId, $newSelectorChain, $this->frameSelector);
    }

    /**
     * @param array<string, mixed> $options
     */
    /**
     * @param array<string, mixed>|GetByOptions $options
     */
    public function getByAltText(string $text, array|GetByOptions $options = []): self
    {
        $options = GetByOptions::from($options);

        return $this->locator(\sprintf('[alt="%s"]', $text));
    }

    /**
     * @param array<string, mixed>|GetByOptions $options
     */
    public function getByLabel(string $text, array|GetByOptions $options = []): self
    {
        $options = GetByOptions::from($options);

        return $this->locator(\sprintf('label:text-is("%s") >> nth=0', $text));
    }

    /**
     * @param array<string, mixed>|GetByOptions $options
     */
    public function getByPlaceholder(string $text, array|GetByOptions $options = []): self
    {
        $options = GetByOptions::from($options);

        return $this->locator(\sprintf('[placeholder="%s"]', $text));
    }

    /**
     * @param array<string, mixed>|GetByRoleOptions $options
     */
    public function getByRole(string $role, array|GetByRoleOptions $options = []): self
    {
        $options = GetByRoleOptions::from($options);
        $selector = RoleSelectorBuilder::buildSelector($role, $options->toArray());

        return $this->locator($selector);
    }

    public function getByTestId(string $testId): self
    {
        return $this->locator(\sprintf('[data-testid="%s"]', $testId));
    }

    /**
     * @param array<string, mixed>|GetByOptions $options
     */
    public function getByText(string $text, array|GetByOptions $options = []): self
    {
        $options = GetByOptions::from($options);

        return $this->locator(\sprintf('text="%s"', $text));
    }

    /**
     * @param array<string, mixed>|GetByOptions $options
     */
    public function getByTitle(string $text, array|GetByOptions $options = []): self
    {
        $options = GetByOptions::from($options);

        return $this->locator(\sprintf('[title="%s"]', $text));
    }

    /**
     * @param array<string, mixed>|WaitForOptions $options
     */
    public function waitFor(array|WaitForOptions $options = []): void
    {
        $options = WaitForOptions::from($options);
        $this->sendCommand('locator.waitFor', ['options' => $options->toArray()]);
    }

    /**
     * @param string|array<string>                     $values
     * @param array<string, mixed>|SelectOptionOptions $options
     *
     * @return array<string>
     */
    public function selectOption(string|array $values, array|SelectOptionOptions $options = []): array
    {
        $options = SelectOptionOptions::from($options);
        $response = $this->sendCommand('locator.selectOption', ['values' => $values, 'options' => $options->toArray()]);
        $values = $response['values'] ?? [];
        if (!is_array($values)) {
            return [];
        }

        return array_filter($values, 'is_string');
    }

    /**
     * @param string|array<string>                      $files
     * @param array<string, mixed>|SetInputFilesOptions $options
     */
    public function setInputFiles(string|array $files, array|SetInputFilesOptions $options = []): void
    {
        $options = SetInputFilesOptions::from($options);
        $fileArray = \is_array($files) ? $files : [$files];

        $this->logger->debug('Setting input files on locator', [
            'selector' => (string) $this->selectorChain,
            'files' => $fileArray,
        ]);

        foreach ($fileArray as $file) {
            if (!\file_exists($file)) {
                $this->logger->error('File not found for input upload', ['file' => $file]);
                throw new PlaywrightException(\sprintf('File not found: %s', $file));
            }
        }

        try {
            $this->sendCommand('locator.setInputFiles', ['files' => $fileArray, 'options' => $options->toArray()]);
            $this->logger->info('Successfully set input files on locator', [
                'selector' => (string) $this->selectorChain,
                'fileCount' => \count($fileArray),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to set input files on locator', [
                'selector' => (string) $this->selectorChain,
                'files' => $fileArray,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed>|FillOptions $options
     */
    public function fill(string $value, array|FillOptions $options = []): void
    {
        $options = FillOptions::from($options);
        $this->waitForActionable();
        $this->sendCommand('locator.fill', ['value' => $value, 'options' => $options->toArray()]);
    }

    /**
     * @param array<string, mixed>|TypeOptions $options
     */
    public function type(string $text, array|TypeOptions $options = []): void
    {
        $options = TypeOptions::from($options);
        $this->sendCommand('locator.type', ['text' => $text, 'options' => $options->toArray()]);
    }

    /**
     * @param array<string, mixed>|PressOptions $options
     */
    public function press(string $key, array|PressOptions $options = []): void
    {
        $options = PressOptions::from($options);
        $this->sendCommand('locator.press', ['key' => $key, 'options' => $options->toArray()]);
    }

    /**
     * @param array<string, mixed>|CheckOptions $options
     */
    public function check(array|CheckOptions $options = []): void
    {
        $options = CheckOptions::from($options);
        $this->sendCommand('locator.check', ['options' => $options->toArray()]);
    }

    /**
     * @param array<string, mixed>|UncheckOptions $options
     */
    public function uncheck(array|UncheckOptions $options = []): void
    {
        $options = UncheckOptions::from($options);
        $this->sendCommand('locator.uncheck', ['options' => $options->toArray()]);
    }

    /**
     * @param array<string, mixed>|HoverOptions $options
     */
    public function hover(array|HoverOptions $options = []): void
    {
        $options = HoverOptions::from($options);
        $this->sendCommand('locator.hover', ['options' => $options->toArray()]);

        $this->transport->processEvents();
    }

    /**
     * Drag this element to the target element.
     *
     * Supports the following options:
     * - sourcePosition: {x: int, y: int} - A point to use relative to the top-left corner of element
     * - targetPosition: {x: int, y: int} - A point to use relative to the top-left corner of target element
     * - force: bool - Whether to bypass the actionability checks
     * - timeout: int - Maximum time in milliseconds
     *
     * @param array<string, mixed>|DragToOptions $options
     */
    public function dragTo(LocatorInterface $target, array|DragToOptions $options = []): void
    {
        $options = DragToOptions::from($options);
        $this->waitForActionable();

        $targetSelector = $target->getSelector();

        $this->sendCommand('locator.dragAndDrop', [
            'target' => $targetSelector,
            'options' => $options->toArray(),
        ]);

        $this->transport->processEvents();
    }

    /**
     * @param array<string, mixed>|TextContentOptions $options
     */
    public function textContent(array|TextContentOptions $options = []): ?string
    {
        $options = TextContentOptions::from($options);
        $response = $this->sendCommand('locator.textContent', ['options' => $options->toArray()]);
        $value = $response['value'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed>|GetAttributeOptions $options
     */
    public function getAttribute(string $name, array|GetAttributeOptions $options = []): ?string
    {
        $options = GetAttributeOptions::from($options);
        $response = $this->sendCommand('locator.getAttribute', ['name' => $name, 'options' => $options->toArray()]);
        $value = $response['value'] ?? null;

        return is_string($value) ? $value : null;
    }

    public function count(): int
    {
        $response = $this->sendCommand('locator.count');
        $value = $response['value'] ?? 0;

        return is_int($value) ? $value : 0;
    }

    /**
     * @return array<LocatorInterface>
     */
    public function all(): array
    {
        $count = $this->count();
        $locators = [];
        for ($i = 0; $i < $count; ++$i) {
            $locators[] = $this->nth($i);
        }

        return $locators;
    }

    public function first(): self
    {
        return $this->nth(0);
    }

    public function last(): self
    {
        return $this->nth(-1);
    }

    public function nth(int $index): self
    {
        $newSelector = $this->selectorChain." >> nth=$index";

        return new Locator($this->transport, $this->pageId, $newSelector, $this->frameSelector);
    }

    public function evaluate(string $expression, mixed $arg = null): mixed
    {
        $normalized = self::normalizeForLocator($expression);
        $response = $this->sendCommand('locator.evaluate', ['expression' => $normalized, 'arg' => $arg]);

        return $response['value'] ?? null;
    }

    public function frameLocator(string $selector): FrameLocatorInterface
    {
        $newFrameSelector = $this->selectorChain.' >> '.$selector;

        return new FrameLocator($this->transport, $this->pageId, $newFrameSelector);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function sendCommand(string $action, array $params = []): array
    {
        $payload = array_merge($params, [
            'action' => $action,
            'pageId' => $this->pageId,
            'selector' => (string) $this->selectorChain,
        ]);

        if ($this->frameSelector) {
            $payload['frameSelector'] = $this->frameSelector;
        }

        $response = $this->transport->send($payload);

        if (isset($response['error'])) {
            $error = $response['error'];
            $errorMessage = is_string($error) ? $error : 'Unknown locator error';
            throw new PlaywrightException($errorMessage);
        }

        return $response;
    }

    private static function normalizeForLocator(string $expression): string
    {
        $trimmed = ltrim($expression);

        if (self::isFunctionLike($trimmed)) {
            return $expression;
        }

        if (self::startsWithReturn($trimmed)) {
            return '(el, arg) => { '.$trimmed.' }';
        }

        return $expression;
    }

    private static function isFunctionLike(string $s): bool
    {
        return (bool) preg_match('/^((async\s+)?function\b|\([^)]*\)\s*=>|[A-Za-z_$][A-Za-z0-9_$]*\s*=>|async\s*\([^)]*\)\s*=>)/', $s);
    }

    private static function startsWithReturn(string $s): bool
    {
        return (bool) preg_match('/^return\b/', $s);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function waitForActionable(array $options = []): void
    {
        $timeout = $this->extractTimeout($options);
        $this->waitForCondition(
            fn () => $this->isVisible() && $this->isEnabled(),
            $timeout,
            'Element not actionable'
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function extractTimeout(array $options, int $default = 30000): int
    {
        $timeout = $options['timeout'] ?? $default;

        return is_int($timeout) && $timeout > 0 ? $timeout : $default;
    }

    private function waitForCondition(callable $condition, int $timeoutMs, string $message): void
    {
        $start = microtime(true);
        $timeoutSeconds = $timeoutMs / 1000;

        while ((microtime(true) - $start) < $timeoutSeconds) {
            try {
                if ($condition()) {
                    return;
                }
            } catch (PlaywrightException $e) {
            }

            usleep(100000);
        }

        throw new TimeoutException(sprintf('%s (timeout: %dms)', $message, $timeoutMs));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForAttached(array $options = []): void
    {
        $timeout = $this->extractTimeout($options);
        $this->waitForCondition(
            fn () => $this->isAttached(),
            $timeout,
            'Element not attached'
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForDetached(array $options = []): void
    {
        $timeout = $this->extractTimeout($options);
        $this->waitForCondition(
            fn () => !$this->isAttached(),
            $timeout,
            'Element still attached'
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForVisible(array $options = []): void
    {
        $timeout = $this->extractTimeout($options);
        $this->waitForCondition(
            fn () => $this->isVisible(),
            $timeout,
            'Element not visible'
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForHidden(array $options = []): void
    {
        $timeout = $this->extractTimeout($options);
        $this->waitForCondition(
            fn () => $this->isHidden(),
            $timeout,
            'Element still visible'
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForEnabled(array $options = []): void
    {
        $timeout = $this->extractTimeout($options);
        $this->waitForCondition(
            fn () => $this->isEnabled(),
            $timeout,
            'Element not enabled'
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForText(string $text, array $options = []): void
    {
        $timeout = $this->extractTimeout($options);
        $this->waitForCondition(
            fn () => str_contains($this->textContent() ?? '', $text),
            $timeout,
            sprintf('Element does not contain text: %s', $text)
        );
    }

    /**
     * @param array<string, mixed>|FilterOptions $options
     */
    public function filter(array|FilterOptions $options = []): self
    {
        $options = FilterOptions::from($options);
        $chain = clone $this->selectorChain;

        if (null !== $options->hasText) {
            $chain->addFilter(\sprintf(':has-text("%s")', $options->hasText));
        }

        if (null !== $options->hasNotText) {
            $chain->addFilter(\sprintf(':not(:has-text("%s"))', $options->hasNotText));
        }

        if (null !== $options->has) {
            $chain->addFilter(\sprintf(':has(%s)', $options->has->getSelector()));
        }

        if (null !== $options->hasNot) {
            $chain->addFilter(\sprintf(':not(:has(%s))', $options->hasNot->getSelector()));
        }

        return new self($this->transport, $this->pageId, $chain, $this->frameSelector, $this->logger);
    }

    public function and(LocatorInterface $locator): self
    {
        $chain = $this->selectorChain->append($locator->getSelector());

        return new self($this->transport, $this->pageId, $chain, $this->frameSelector, $this->logger);
    }

    public function or(LocatorInterface $locator): self
    {
        $combined = \sprintf('%s, %s', $this->selectorChain->toString(), $locator->getSelector());
        $chain = new SelectorChain($combined);

        return new self($this->transport, $this->pageId, $chain, $this->frameSelector, $this->logger);
    }

    public function describe(string $description): self
    {
        return $this;
    }

    public function contentFrame(): FrameLocatorInterface
    {
        return new FrameLocator($this->transport, $this->pageId, $this->selectorChain->toString(), $this->logger);
    }
}
