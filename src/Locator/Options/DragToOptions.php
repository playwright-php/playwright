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

use Playwright\Exception\InvalidArgumentException;

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

        if (!\is_array($options)) {
            throw new InvalidArgumentException('Options must be an array or an instance of DragToOptions');
        }

        return new self(
            $options['sourcePosition'] ?? null,
            $options['targetPosition'] ?? null,
            $options['force'] ?? null,
            $options['noWaitAfter'] ?? null,
            $options['steps'] ?? null,
            $options['timeout'] ?? null,
            $options['trial'] ?? null,
        );
    }
}
