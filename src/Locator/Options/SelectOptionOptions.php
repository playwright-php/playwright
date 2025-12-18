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

class SelectOptionOptions
{
    public function __construct(
        public ?bool $force = null,
        public ?bool $noWaitAfter = null,
        public ?float $timeout = null,
    ) {
    }

    public function toArray(): array
    {
        $options = [];
        if (null !== $this->force) {
            $options['force'] = $this->force;
        }
        if (null !== $this->noWaitAfter) {
            $options['noWaitAfter'] = $this->noWaitAfter;
        }
        if (null !== $this->timeout) {
            $options['timeout'] = $this->timeout;
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
            throw new InvalidArgumentException('Options must be an array or an instance of SelectOptionOptions');
        }

        return new self(
            $options['force'] ?? null,
            $options['noWaitAfter'] ?? null,
            $options['timeout'] ?? null,
        );
    }
}
