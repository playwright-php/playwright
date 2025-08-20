<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Locator;

use PlaywrightPHP\Exception\PlaywrightException;
use PlaywrightPHP\Exception\TimeoutException;
use PlaywrightPHP\FrameLocator\FrameLocator;
use PlaywrightPHP\FrameLocator\FrameLocatorInterface;
use PlaywrightPHP\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class Locator implements LocatorInterface
{
    private SelectorChain $selectorChain;
    private array $waitOptions = [];
    private LoggerInterface $logger;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $pageId,
        string|SelectorChain $selector,
        private readonly ?string $frameSelector = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
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

    public function click(array $options = []): void
    {
        $this->waitForActionable();
        $this->sendCommand('locator.click', ['options' => $options]);
    }

    public function dblclick(array $options = []): void
    {
        $this->sendCommand('locator.dblclick', ['options' => $options]);
    }

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

    public function screenshot(?string $path = null, array $options = []): ?string
    {
        if ($path) {
            $options['path'] = $path;
        }
        $response = $this->sendCommand('locator.screenshot', ['options' => $options]);

        return $path ? null : $response['binary'];
    }

    public function allInnerTexts(): array
    {
        return $this->sendCommand('locator.allInnerTexts')['value'];
    }

    public function allTextContents(): array
    {
        return $this->sendCommand('locator.allTextContents')['value'];
    }

    public function innerHTML(): string
    {
        return $this->sendCommand('locator.innerHTML')['value'];
    }

    public function innerText(): string
    {
        return $this->sendCommand('locator.innerText')['value'];
    }

    public function inputValue(): string
    {
        return $this->sendCommand('locator.inputValue')['value'];
    }

    public function isAttached(): bool
    {
        return $this->sendCommand('locator.isAttached')['value'];
    }

    public function isChecked(): bool
    {
        return $this->sendCommand('locator.isChecked')['value'];
    }

    public function isDisabled(): bool
    {
        return $this->sendCommand('locator.isDisabled')['value'];
    }

    public function isEditable(): bool
    {
        return $this->sendCommand('locator.isEditable')['value'];
    }

    public function isEmpty(): bool
    {
        return $this->sendCommand('locator.isEmpty')['value'];
    }

    public function isEnabled(): bool
    {
        return $this->sendCommand('locator.isEnabled')['value'];
    }

    public function isHidden(): bool
    {
        return $this->sendCommand('locator.isHidden')['value'];
    }

    public function isVisible(): bool
    {
        return $this->sendCommand('locator.isVisible')['value'];
    }

    public function locator(string $selector): self
    {
        $newSelectorChain = clone $this->selectorChain;
        $newSelectorChain->append($selector);

        return new Locator($this->transport, $this->pageId, $newSelectorChain, $this->frameSelector);
    }

    public function waitFor(array $options = []): void
    {
        $this->sendCommand('locator.waitFor', ['options' => $options]);
    }

    public function selectOption($values, array $options = []): array
    {
        return $this->sendCommand('locator.selectOption', ['values' => $values, 'options' => $options])['values'];
    }

    public function setInputFiles($files, array $options = []): void
    {
        // Normalize files to array
        $fileArray = \is_array($files) ? $files : [$files];

        $this->logger->debug('Setting input files on locator', [
            'selector' => (string) $this->selectorChain,
            'files' => $fileArray,
        ]);

        // Validate that all files exist
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

    public function fill(string $value, array $options = []): void
    {
        $this->waitForActionable();
        $this->sendCommand('locator.fill', ['value' => $value, 'options' => $options]);
    }

    public function type(string $text, array $options = []): void
    {
        $this->sendCommand('locator.type', ['text' => $text, 'options' => $options]);
    }

    public function press(string $key, array $options = []): void
    {
        $this->sendCommand('locator.press', ['key' => $key, 'options' => $options]);
    }

    public function check(array $options = []): void
    {
        $this->sendCommand('locator.check', ['options' => $options]);
    }

    public function uncheck(array $options = []): void
    {
        $this->sendCommand('locator.uncheck', ['options' => $options]);
    }

    public function hover(array $options = []): void
    {
        $this->sendCommand('locator.hover', ['options' => $options]);

        // Process any pending events after hover
        $this->transport->processEvents();
    }

    public function textContent(array $options = []): ?string
    {
        return $this->sendCommand('locator.textContent', ['options' => $options])['value'];
    }

    public function getAttribute(string $name, array $options = []): ?string
    {
        return $this->sendCommand('locator.getAttribute', ['name' => $name, 'options' => $options])['value'];
    }

    public function count(): int
    {
        return $this->sendCommand('locator.count')['value'];
    }

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
        $response = $this->sendCommand('locator.evaluate', ['expression' => $expression, 'arg' => $arg]);

        return $response['value'] ?? null;
    }

    public function frameLocator(string $selector): FrameLocatorInterface
    {
        $newFrameSelector = $this->selectorChain.' >> '.$selector;

        return new FrameLocator($this->transport, $this->pageId, $newFrameSelector);
    }

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
            throw new PlaywrightException($response['error']);
        }

        return $response;
    }

    private function waitForActionable(array $options = []): void
    {
        $timeout = $options['timeout'] ?? 30000; // 30 seconds default
        $this->waitForCondition(
            fn () => $this->isVisible() && $this->isEnabled(),
            $timeout,
            'Element not actionable'
        );
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
                // Ignore exceptions during condition checks, keep retrying
            }

            usleep(100000); // 100ms
        }

        throw new TimeoutException(sprintf('%s (timeout: %dms)', $message, $timeoutMs));
    }

    public function waitForAttached(array $options = []): void
    {
        $timeout = $options['timeout'] ?? 30000;
        $this->waitForCondition(
            fn () => $this->isAttached(),
            $timeout,
            'Element not attached'
        );
    }

    public function waitForDetached(array $options = []): void
    {
        $timeout = $options['timeout'] ?? 30000;
        $this->waitForCondition(
            fn () => !$this->isAttached(),
            $timeout,
            'Element still attached'
        );
    }

    public function waitForVisible(array $options = []): void
    {
        $timeout = $options['timeout'] ?? 30000;
        $this->waitForCondition(
            fn () => $this->isVisible(),
            $timeout,
            'Element not visible'
        );
    }

    public function waitForHidden(array $options = []): void
    {
        $timeout = $options['timeout'] ?? 30000;
        $this->waitForCondition(
            fn () => $this->isHidden(),
            $timeout,
            'Element still visible'
        );
    }

    public function waitForEnabled(array $options = []): void
    {
        $timeout = $options['timeout'] ?? 30000;
        $this->waitForCondition(
            fn () => $this->isEnabled(),
            $timeout,
            'Element not enabled'
        );
    }

    public function waitForText(string $text, array $options = []): void
    {
        $timeout = $options['timeout'] ?? 30000;
        $this->waitForCondition(
            fn () => str_contains($this->textContent() ?? '', $text),
            $timeout,
            sprintf('Element does not contain text: %s', $text)
        );
    }
}
