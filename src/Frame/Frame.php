<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Frame;

use PlaywrightPHP\Exception\PlaywrightException;
use PlaywrightPHP\Locator\Locator;
use PlaywrightPHP\Locator\LocatorInterface;
use PlaywrightPHP\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
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
            throw new \RuntimeException('Invalid frame.name response');
        }

        return $value;
    }

    public function url(): string
    {
        $response = $this->sendCommand('frame.url');
        $value = $response['value'] ?? null;
        if (!is_string($value)) {
            throw new \RuntimeException('Invalid frame.url response');
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
