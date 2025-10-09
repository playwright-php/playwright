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

use Playwright\Locator\Locator;
use Playwright\Locator\LocatorInterface;
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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
