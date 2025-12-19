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

class FrameQueryOptions
{
    public function __construct(
        public ?string $name = null,
        public ?string $url = null,
        public ?string $urlRegex = null,
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
        if (null !== $this->url) {
            $options['url'] = $this->url;
        }
        if (null !== $this->urlRegex) {
            $options['urlRegex'] = $this->urlRegex;
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
        /** @var string|null $url */
        $url = $options['url'] ?? null;
        /** @var string|null $urlRegex */
        $urlRegex = $options['urlRegex'] ?? null;

        return new self($name, $url, $urlRegex);
    }
}
