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

namespace Playwright\Page\Options;

final readonly class DragAndDropOptions
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
        public ?string $scroll = null,
        public ?bool $strict = null,
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
        foreach (['sourcePosition', 'targetPosition', 'force', 'noWaitAfter', 'scroll', 'strict', 'steps', 'timeout', 'trial'] as $option) {
            if (null !== $this->{$option}) {
                $options[$option] = $this->{$option};
            }
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
        /** @var string|null $scroll */
        $scroll = $options['scroll'] ?? null;
        /** @var bool|null $strict */
        $strict = $options['strict'] ?? null;
        /** @var int|null $steps */
        $steps = $options['steps'] ?? null;
        /** @var float|null $timeout */
        $timeout = $options['timeout'] ?? null;
        /** @var bool|null $trial */
        $trial = $options['trial'] ?? null;

        return new self($sourcePosition, $targetPosition, $force, $noWaitAfter, $scroll, $strict, $steps, $timeout, $trial);
    }
}
