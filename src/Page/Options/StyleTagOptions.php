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

class StyleTagOptions
{
    public function __construct(
        public ?string $url = null,
        public ?string $path = null,
        public ?string $content = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        if (null !== $this->url) {
            $options['url'] = $this->url;
        }
        if (null !== $this->path) {
            $options['path'] = $this->path;
        }
        if (null !== $this->content) {
            $options['content'] = $this->content;
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

        /** @var string|null $url */
        $url = $options['url'] ?? null;
        /** @var string|null $path */
        $path = $options['path'] ?? null;
        /** @var string|null $content */
        $content = $options['content'] ?? null;

        return new self($url, $path, $content);
    }
}
