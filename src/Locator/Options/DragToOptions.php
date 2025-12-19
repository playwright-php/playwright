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

namespace Playwright\Locator\Options;

class DragToOptions
{
    /**
     * @param array{x: float, y: float}|null $sourcePosition
     * @param array{x: float, y: float}|null $targetPosition
     */
    public function __construct(
        public ?array $sourcePosition = null,
        public ?array $targetPosition = null,
        public ?bool $force = null,
        public ?bool $noWaitAfter = null,
        public ?int $steps = null,
        public ?float $timeout = null,
        public ?bool $trial = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        if (null !== $this->sourcePosition) {
            $options['sourcePosition'] = $this->sourcePosition;
        }
        if (null !== $this->targetPosition) {
            $options['targetPosition'] = $this->targetPosition;
        }
        if (null !== $this->force) {
            $options['force'] = $this->force;
        }
        if (null !== $this->noWaitAfter) {
            $options['noWaitAfter'] = $this->noWaitAfter;
        }
        if (null !== $this->steps) {
            $options['steps'] = $this->steps;
        }
        if (null !== $this->timeout) {
            $options['timeout'] = $this->timeout;
        }
        if (null !== $this->trial) {
            $options['trial'] = $this->trial;
        }

        return $options;
    }

    /**
     * @param array<string, mixed>|self $options
     */
    public static function from(array|self $options = []): self
    {
        if ($options instanceof self) {
            return $options;
        }

        /** @var array{x: float, y: float}|null $sourcePosition */
        $sourcePosition = $options['sourcePosition'] ?? null;
        /** @var array{x: float, y: float}|null $targetPosition */
        $targetPosition = $options['targetPosition'] ?? null;
        /** @var bool|null $force */
        $force = $options['force'] ?? null;
        /** @var bool|null $noWaitAfter */
        $noWaitAfter = $options['noWaitAfter'] ?? null;
        /** @var int|null $steps */
        $steps = $options['steps'] ?? null;
        /** @var float|null $timeout */
        $timeout = $options['timeout'] ?? null;
        /** @var bool|null $trial */
        $trial = $options['trial'] ?? null;

        return new self($sourcePosition, $targetPosition, $force, $noWaitAfter, $steps, $timeout, $trial);
    }
}
