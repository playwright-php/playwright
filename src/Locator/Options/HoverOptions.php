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

class HoverOptions
{
    /**
     * @param array<string>|null             $modifiers
     * @param array{x: float, y: float}|null $position
     */
    public function __construct(
        public ?array $modifiers = null,
        public ?array $position = null,
        public ?bool $force = null,
        public ?bool $noWaitAfter = null,
        public ?float $timeout = null,
        public ?bool $trial = null,
    ) {
    }

    public function toArray(): array
    {
        $options = [];
        if (null !== $this->modifiers) {
            $options['modifiers'] = $this->modifiers;
        }
        if (null !== $this->position) {
            $options['position'] = $this->position;
        }
        if (null !== $this->force) {
            $options['force'] = $this->force;
        }
        if (null !== $this->noWaitAfter) {
            $options['noWaitAfter'] = $this->noWaitAfter;
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
            throw new InvalidArgumentException('Options must be an array or an instance of HoverOptions');
        }

        return new self(
            $options['modifiers'] ?? null,
            $options['position'] ?? null,
            $options['force'] ?? null,
            $options['noWaitAfter'] ?? null,
            $options['timeout'] ?? null,
            $options['trial'] ?? null,
        );
    }
}
