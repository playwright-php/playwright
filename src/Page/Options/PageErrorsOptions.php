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

final readonly class PageErrorsOptions
{
    /**
     * An omitted filter uses Playwright's default: 'since-navigation'.
     *
     * @param 'all'|'since-navigation'|null $filter
     */
    public function __construct(
        public ?string $filter = null,
    ) {
        if (null !== $filter && !in_array($filter, ['all', 'since-navigation'], true)) {
            throw new InvalidArgumentException('The page errors filter must be "all" or "since-navigation".');
        }
    }

    /**
     * @return array{filter?: 'all'|'since-navigation'}
     */
    public function toArray(): array
    {
        return null === $this->filter ? [] : ['filter' => $this->filter];
    }

    /**
     * @param array<string, mixed>|self $options
     */
    public static function from(array|self $options = []): self
    {
        if ($options instanceof self) {
            return $options;
        }

        /** @var 'all'|'since-navigation'|null $filter */
        $filter = $options['filter'] ?? null;

        return new self($filter);
    }
}
