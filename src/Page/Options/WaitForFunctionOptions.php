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

final class WaitForFunctionOptions
{
    /**
     * @param float|null       $timeout Timeout in ms
     * @param float|'raf'|null $polling Polling-Intervall in ms oder 'raf'
     */
    public function __construct(
        public ?float $timeout = null,
        public float|string|null $polling = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];

        if (null !== $this->timeout) {
            $options['timeout'] = $this->timeout;
        }

        if (null !== $this->polling) {
            $options['polling'] = $this->polling;
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

        /** @var float|null $timeout */
        $timeout = $options['timeout'] ?? null;

        /** @var float|'raf'|null $polling */
        $polling = $options['polling'] ?? null;

        return new self($timeout, $polling);
    }
}
