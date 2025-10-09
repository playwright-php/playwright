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

use Playwright\Exception\PlaywrightException;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Locator\Locator;
use Playwright\Locator\LocatorInterface;
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Frame implements FrameInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $pageId,
        private readonly string $frameSelector,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function __toString(): string
    {
        return 'Frame(selector="'.$this->frameSelector.'")';
    }

    public function locator(string $selector): LocatorInterface
    {
        $this->logger->debug('Creating locator in frame', [
            'frameSelector' => $this->frameSelector,
            'selector' => $selector,
        ]);

        return new Locator($this->transport, $this->pageId, $selector, $this->frameSelector, $this->logger);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getByAltText(string $text, array $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('[alt="%s"]', $text));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getByLabel(string $text, array $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('label:text-is("%s") >> nth=0', $text));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getByPlaceholder(string $text, array $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('[placeholder="%s"]', $text));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getByRole(string $role, array $options = []): LocatorInterface
    {
        return $this->locator($role);
    }

    public function getByTestId(string $testId): LocatorInterface
    {
        return $this->locator(\sprintf('[data-testid="%s"]', $testId));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getByText(string $text, array $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('text="%s"', $text));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getByTitle(string $text, array $options = []): LocatorInterface
    {
        return $this->locator(\sprintf('[title="%s"]', $text));
    }

    public function frameLocator(string $selector): FrameLocatorInterface
    {
        $newSelector = $this->frameSelector.' >> '.$selector;

        $this->logger->debug('Creating nested frame locator from frame', [
            'parentFrameSelector' => $this->frameSelector,
            'childSelector' => $selector,
            'newSelector' => $newSelector,
        ]);

        return new FrameLocator($this->transport, $this->pageId, $newSelector, $this->logger);
    }

    public function owner(): LocatorInterface
    {
        $this->logger->debug('Creating owner locator for frame', [
            'frameSelector' => $this->frameSelector,
        ]);

        return new Locator($this->transport, $this->pageId, $this->frameSelector, null, $this->logger);
    }

    public function name(): string
    {
        $response = $this->sendCommand('frame.name');
        $value = $response['value'] ?? null;
        if (!is_string($value)) {
            throw new ProtocolErrorException('Invalid frame.name response', 0);
        }

        return $value;
    }

    public function url(): string
    {
        $response = $this->sendCommand('frame.url');
        $value = $response['value'] ?? null;
        if (!is_string($value)) {
            throw new ProtocolErrorException('Invalid frame.url response', 0);
        }

        return $value;
    }

    public function isDetached(): bool
    {
        $response = $this->sendCommand('frame.isDetached');

        return true === ($response['value'] ?? false);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForLoadState(string $state = 'load', array $options = []): self
    {
        $this->sendCommand('frame.waitForLoadState', ['state' => $state, 'options' => $options]);

        return $this;
    }

    public function parentFrame(): ?FrameInterface
    {
        $response = $this->sendCommand('frame.parent');
        $selector = $response['selector'] ?? null;

        return is_string($selector)
            ? new Frame($this->transport, $this->pageId, $selector, $this->logger)
            : null;
    }

    /**
     * @return array<FrameInterface>
     */
    public function childFrames(): array
    {
        $response = $this->sendCommand('frame.children');
        $frames = $response['frames'] ?? [];
        if (!is_array($frames)) {
            return [];
        }

        $result = [];
        foreach ($frames as $frameData) {
            if (is_array($frameData) && isset($frameData['selector']) && is_string($frameData['selector'])) {
                $result[] = new Frame($this->transport, $this->pageId, $frameData['selector'], $this->logger);
            }
        }

        return $result;
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
            'frameSelector' => $this->frameSelector,
        ]);

        $response = $this->transport->send($payload);

        if (isset($response['error'])) {
            $error = $response['error'];
            $errorMessage = is_string($error) ? $error : 'Unknown frame error';
            throw new PlaywrightException($errorMessage);
        }

        return $response;
    }
}
