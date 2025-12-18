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

class StyleTagOptions
{
    public function __construct(
        public ?string $url = null,
        public ?string $path = null,
        public ?string $content = null,
    ) {
    }

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
     * @param array{url?: string, path?: string, content?: string}|self $options
     */
    public static function from(array|self $options = []): self
    {
        if ($options instanceof self) {
            return $options;
        }

        if (!\is_array($options)) {
            throw new InvalidArgumentException('Options must be an array or an instance of StyleTagOptions');
        }

        return new self(
            $options['url'] ?? null,
            $options['path'] ?? null,
            $options['content'] ?? null,
        );
    }
}
