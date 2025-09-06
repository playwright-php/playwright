<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Frame;

use PlaywrightPHP\Locator\Locator;
use PlaywrightPHP\Locator\LocatorInterface;
use PlaywrightPHP\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class FrameLocator implements FrameLocatorInterface
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

    public function locator(string $selector): LocatorInterface
    {
        $this->logger->debug('Creating locator within frame', [
            'frameSelector' => $this->frameSelector,
            'selector' => $selector,
        ]);

        return new Locator($this->transport, $this->pageId, $selector, $this->frameSelector, $this->logger);
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
        $newSelector = $this->frameSelector." >> nth=$index";

        $this->logger->debug('Creating nth frame locator', [
            'frameSelector' => $this->frameSelector,
            'index' => $index,
            'newSelector' => $newSelector,
        ]);

        return new FrameLocator($this->transport, $this->pageId, $newSelector, $this->logger);
    }

    public function frameLocator(string $selector): self
    {
        $newSelector = $this->frameSelector.' >> '.$selector;

        $this->logger->debug('Creating nested frame locator', [
            'parentFrameSelector' => $this->frameSelector,
            'childSelector' => $selector,
            'newSelector' => $newSelector,
        ]);

        return new FrameLocator($this->transport, $this->pageId, $newSelector, $this->logger);
    }

    public function getSelector(): string
    {
        return $this->frameSelector;
    }

    public function owner(): LocatorInterface
    {
        $this->logger->debug('Creating owner locator for frame', [
            'frameSelector' => $this->frameSelector,
        ]);

        return new Locator($this->transport, $this->pageId, $this->frameSelector, null, $this->logger);
    }

    public function __toString(): string
    {
        return 'FrameLocator(selector="'.$this->frameSelector.'")';
    }
}
