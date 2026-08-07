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

use Playwright\Regex;

final readonly class StartHarOptions
{
    /**
     * @param 'attach'|'embed'|'omit'|null $content
     * @param 'full'|'minimal'|null        $mode
     */
    public function __construct(
        public ?string $content = null,
        public ?string $mode = null,
        public ?string $resourcesDir = null,
        public string|Regex|null $urlFilter = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        if (null !== $this->content) {
            $options['content'] = $this->content;
        }
        if (null !== $this->mode) {
            $options['mode'] = $this->mode;
        }
        if (null !== $this->resourcesDir) {
            $options['resourcesDir'] = $this->resourcesDir;
        }
        if ($this->urlFilter instanceof Regex) {
            $options['urlFilterRegex'] = $this->urlFilter->pattern;
        } elseif (null !== $this->urlFilter) {
            $options['urlFilter'] = $this->urlFilter;
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

        /** @var 'attach'|'embed'|'omit'|null $content */
        $content = $options['content'] ?? null;
        /** @var 'full'|'minimal'|null $mode */
        $mode = $options['mode'] ?? null;
        /** @var string|null $resourcesDir */
        $resourcesDir = $options['resourcesDir'] ?? null;
        /** @var string|Regex|null $urlFilter */
        $urlFilter = $options['urlFilter'] ?? null;

        return new self($content, $mode, $resourcesDir, $urlFilter);
    }
}
