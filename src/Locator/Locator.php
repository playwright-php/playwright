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
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Locator implements LocatorInterface
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
     * @param array<string, mixed> $options
     */
    public function click(array $options = []): void
    {
        $this->waitForActionable();
        $this->sendCommand('locator.click', ['options' => $options]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function dblclick(array $options = []): void
    {
        $this->sendCommand('locator.dblclick', ['options' => $options]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function clear(array $options = []): void
    {
        $this->sendCommand('locator.clear', ['options' => $options]);
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
     * @param array<string, mixed> $options
     */
    public function screenshot(?string $path = null, array $options = []): ?string
    {
        if ($path) {
            $options['path'] = $path;
        }
        $response = $this->sendCommand('locator.screenshot', ['options' => $options]);

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
    public function waitFor(array $options = []): void
    {
        $this->sendCommand('locator.waitFor', ['options' => $options]);
    }

    /**
     * @param string|array<string> $values
     * @param array<string, mixed> $options
     *
     * @return array<string>
     */
    public function selectOption(string|array $values, array $options = []): array
    {
        $response = $this->sendCommand('locator.selectOption', ['values' => $values, 'options' => $options]);
        $values = $response['values'] ?? [];
        if (!is_array($values)) {
            return [];
        }

        return array_filter($values, 'is_string');
    }

    /**
     * @param string|array<string> $files
     * @param array<string, mixed> $options
     */
    public function setInputFiles(string|array $files, array $options = []): void
    {
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
            $this->sendCommand('locator.setInputFiles', ['files' => $fileArray, 'options' => $options]);
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
     * @param array<string, mixed> $options
     */
    public function fill(string $value, array $options = []): void
    {
        $this->waitForActionable();
        $this->sendCommand('locator.fill', ['value' => $value, 'options' => $options]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function type(string $text, array $options = []): void
    {
        $this->sendCommand('locator.type', ['text' => $text, 'options' => $options]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function press(string $key, array $options = []): void
    {
        $this->sendCommand('locator.press', ['key' => $key, 'options' => $options]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function check(array $options = []): void
    {
        $this->sendCommand('locator.check', ['options' => $options]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function uncheck(array $options = []): void
    {
        $this->sendCommand('locator.uncheck', ['options' => $options]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function hover(array $options = []): void
    {
        $this->sendCommand('locator.hover', ['options' => $options]);

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
     * @param array<string, mixed> $options
     */
    public function dragTo(LocatorInterface $target, array $options = []): void
    {
        $this->waitForActionable();

        $targetSelector = $target->getSelector();

        $this->sendCommand('locator.dragAndDrop', [
            'target' => $targetSelector,
            'options' => $options,
        ]);

        $this->transport->processEvents();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function textContent(array $options = []): ?string
    {
        $response = $this->sendCommand('locator.textContent', ['options' => $options]);
        $value = $response['value'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getAttribute(string $name, array $options = []): ?string
    {
        $response = $this->sendCommand('locator.getAttribute', ['name' => $name, 'options' => $options]);
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
        // Detect common JS function patterns: function, async function, arrow functions
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
}
