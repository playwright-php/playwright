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

final readonly class WaitForDownloadOptions
{
    /**
     * @param float|null $timeout Maximum time to wait for the download to start, in milliseconds. 30000 by default, 0 waits forever.
     */
    public function __construct(
        public ?float $timeout = null,
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

        /** @var float|int|null $timeout */
        $timeout = $options['timeout'] ?? null;

        return new self(null === $timeout ? null : (float) $timeout);
    }
}
