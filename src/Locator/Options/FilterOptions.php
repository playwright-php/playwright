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

use Playwright\Locator\LocatorInterface;

final readonly class FilterOptions
{
    public function __construct(
        public ?LocatorInterface $has = null,
        public ?LocatorInterface $hasNot = null,
        public ?string $hasText = null,
        public ?string $hasNotText = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        if (null !== $this->has) {
            $options['has'] = $this->has;
        }
        if (null !== $this->hasNot) {
            $options['hasNot'] = $this->hasNot;
        }
        if (null !== $this->hasText) {
            $options['hasText'] = $this->hasText;
        }
        if (null !== $this->hasNotText) {
            $options['hasNotText'] = $this->hasNotText;
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

        /** @var LocatorInterface|null $has */
        $has = $options['has'] ?? null;
        /** @var LocatorInterface|null $hasNot */
        $hasNot = $options['hasNot'] ?? null;
        /** @var string|null $hasText */
        $hasText = $options['hasText'] ?? null;
        /** @var string|null $hasNotText */
        $hasNotText = $options['hasNotText'] ?? null;

        return new self($has, $hasNot, $hasText, $hasNotText);
    }
}
