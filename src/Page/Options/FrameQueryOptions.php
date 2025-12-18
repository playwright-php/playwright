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

class FrameQueryOptions
{
    public function __construct(
        public ?string $name = null,
        public ?string $url = null,
        public ?string $urlRegex = null,
    ) {
    }

    public function toArray(): array
    {
        $options = [];
        if (null !== $this->name) {
            $options['name'] = $this->name;
        }
        if (null !== $this->url) {
            $options['url'] = $this->url;
        }
        if (null !== $this->urlRegex) {
            $options['urlRegex'] = $this->urlRegex;
        }

        return $options;
    }

    /**
     * @param array{name?: string, url?: string, urlRegex?: string}|self $options
     */
    public static function from(array|self $options = []): self
    {
        if ($options instanceof self) {
            return $options;
        }

        if (!\is_array($options)) {
            throw new InvalidArgumentException('Options must be an array or an instance of FrameQueryOptions');
        }

        return new self(
            $options['name'] ?? null,
            $options['url'] ?? null,
            $options['urlRegex'] ?? null,
        );
    }
}
