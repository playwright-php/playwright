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

use Playwright\Exception\InvalidArgumentException;

final readonly class TypeOptions
{
    public function __construct(
        public ?float $delay = null,
        public ?bool $noWaitAfter = null,
        public ?float $timeout = null,
        public ?bool $strict = null,
    ) {
    }

    public function toArray(): array
    {
        $options = [];
        if (null !== $this->delay) {
            $options['delay'] = $this->delay;
        }
        if (null !== $this->noWaitAfter) {
            $options['noWaitAfter'] = $this->noWaitAfter;
        }
        if (null !== $this->timeout) {
            $options['timeout'] = $this->timeout;
        }
        if (null !== $this->strict) {
            $options['strict'] = $this->strict;
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
            throw new InvalidArgumentException('Options must be an array or an instance of TypeOptions');
        }

        return new self(
            $options['delay'] ?? null,
            $options['noWaitAfter'] ?? null,
            $options['timeout'] ?? null,
            $options['strict'] ?? null,
        );
    }
}
