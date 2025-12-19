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

class DblClickOptions
{
    /**
     * @param 'left'|'right'|'middle'|null   $button
     * @param array<string>|null             $modifiers
     * @param array{x: float, y: float}|null $position
     */
    public function __construct(
        public ?string $button = null,
        public ?float $delay = null,
        public ?array $modifiers = null,
        public ?array $position = null,
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
        if (null !== $this->button) {
            $options['button'] = $this->button;
        }
        if (null !== $this->delay) {
            $options['delay'] = $this->delay;
        }
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

        /** @var 'left'|'right'|'middle'|null $button */
        $button = $options['button'] ?? null;
        /** @var float|null $delay */
        $delay = $options['delay'] ?? null;
        /** @var array<string>|null $modifiers */
        $modifiers = $options['modifiers'] ?? null;
        /** @var array{x: float, y: float}|null $position */
        $position = $options['position'] ?? null;
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

        return new self($button, $delay, $modifiers, $position, $force, $noWaitAfter, $steps, $timeout, $trial);
    }
}
