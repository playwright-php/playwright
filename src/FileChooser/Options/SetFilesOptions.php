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

namespace Playwright\FileChooser\Options;

final readonly class SetFilesOptions
{
    public function __construct(
        public ?bool $noWaitAfter = null,
        public ?float $timeout = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
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

        /** @var bool|null $noWaitAfter */
        $noWaitAfter = $options['noWaitAfter'] ?? null;
        /** @var float|int|null $timeoutValue */
        $timeoutValue = $options['timeout'] ?? null;
        $timeout = null !== $timeoutValue ? (float) $timeoutValue : null;

        return new self($noWaitAfter, $timeout);
    }
}
