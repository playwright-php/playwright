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

namespace Playwright\Tracing\Options;

final readonly class StartChunkOptions
{
    public function __construct(
        public ?string $name = null,
        public ?string $title = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        if (null !== $this->name) {
            $options['name'] = $this->name;
        }
        if (null !== $this->title) {
            $options['title'] = $this->title;
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

        /** @var string|null $name */
        $name = $options['name'] ?? null;
        /** @var string|null $title */
        $title = $options['title'] ?? null;

        return new self($name, $title);
    }
}
