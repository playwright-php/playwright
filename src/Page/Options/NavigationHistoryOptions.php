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

class NavigationHistoryOptions
{
    /**
     * @param 'load'|'domcontentloaded'|'networkidle'|'commit'|null $waitUntil
     */
    public function __construct(
        public ?float $timeout = null,
        public ?string $waitUntil = null,
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
        if (null !== $this->waitUntil) {
            $options['waitUntil'] = $this->waitUntil;
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
        /** @var 'load'|'domcontentloaded'|'networkidle'|'commit'|null $waitUntil */
        $waitUntil = $options['waitUntil'] ?? null;

        return new self($timeout, $waitUntil);
    }
}
