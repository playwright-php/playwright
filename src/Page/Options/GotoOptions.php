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

final readonly class GotoOptions
{
    public function __construct(
        public ?string $referer = null,
        public ?float $timeout = null,
        public ?string $waitUntil = null, // e.g., 'load', 'domcontentloaded', 'networkidle', 'commit'
        public ?bool $navigationRequest = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        if (null !== $this->referer) {
            $options['referer'] = $this->referer;
        }
        if (null !== $this->timeout) {
            $options['timeout'] = $this->timeout;
        }
        if (null !== $this->waitUntil) {
            $options['waitUntil'] = $this->waitUntil;
        }
        if (null !== $this->navigationRequest) {
            $options['navigationRequest'] = $this->navigationRequest;
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

        /** @var string|null $referer */
        $referer = $options['referer'] ?? null;
        /** @var float|null $timeout */
        $timeout = $options['timeout'] ?? null;
        /** @var string|null $waitUntil */
        $waitUntil = $options['waitUntil'] ?? null;
        /** @var bool|null $navigationRequest */
        $navigationRequest = $options['navigationRequest'] ?? null;

        return new self($referer, $timeout, $waitUntil, $navigationRequest);
    }
}
