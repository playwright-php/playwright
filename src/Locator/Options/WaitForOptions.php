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

class WaitForOptions
{
    /**
     * @param 'attached'|'detached'|'visible'|'hidden'|null $state
     */
    public function __construct(
        public ?string $state = null,
        public ?float $timeout = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        if (null !== $this->state) {
            $options['state'] = $this->state;
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

        /** @var 'attached'|'detached'|'visible'|'hidden'|null $state */
        $state = $options['state'] ?? null;
        /** @var float|null $timeout */
        $timeout = $options['timeout'] ?? null;

        return new self($state, $timeout);
    }
}
