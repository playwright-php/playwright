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

final readonly class ScreenshotOptions
{
    /**
     * @param 'png'|'jpeg'|null                                           $type
     * @param array{x: float, y: float, width: float, height: float}|null $clip
     * @param 'disabled'|'allow'|null                                     $animations
     * @param 'hide'|'initial'|null                                       $caret
     * @param 'css'|'device'|null                                         $scale
     * @param array<mixed>|null                                           $mask
     */
    public function __construct(
        public ?string $path = null,
        public ?string $type = null,
        public ?int $quality = null,
        public ?bool $fullPage = null,
        public ?array $clip = null,
        public ?bool $omitBackground = null,
        public ?float $timeout = null,
        public ?string $animations = null,
        public ?string $caret = null,
        public ?string $scale = null,
        public ?array $mask = null,
        public ?string $maskColor = null,
    ) {
    }

    public function toArray(): array
    {
        $options = [];
        if (null !== $this->path) {
            $options['path'] = $this->path;
        }
        if (null !== $this->type) {
            $options['type'] = $this->type;
        }
        if (null !== $this->quality) {
            $options['quality'] = $this->quality;
        }
        if (null !== $this->fullPage) {
            $options['fullPage'] = $this->fullPage;
        }
        if (null !== $this->clip) {
            $options['clip'] = $this->clip;
        }
        if (null !== $this->omitBackground) {
            $options['omitBackground'] = $this->omitBackground;
        }
        if (null !== $this->timeout) {
            $options['timeout'] = $this->timeout;
        }
        if (null !== $this->animations) {
            $options['animations'] = $this->animations;
        }
        if (null !== $this->caret) {
            $options['caret'] = $this->caret;
        }
        if (null !== $this->scale) {
            $options['scale'] = $this->scale;
        }
        if (null !== $this->mask) {
            $options['mask'] = $this->mask;
        }
        if (null !== $this->maskColor) {
            $options['maskColor'] = $this->maskColor;
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

        if (!\is_array($options)) {
            throw new InvalidArgumentException('Options must be an array or an instance of ScreenshotOptions');
        }

        return new self(
            $options['path'] ?? null,
            $options['type'] ?? null,
            $options['quality'] ?? null,
            $options['fullPage'] ?? null,
            $options['clip'] ?? null,
            $options['omitBackground'] ?? null,
            $options['timeout'] ?? null,
            $options['animations'] ?? null,
            $options['caret'] ?? null,
            $options['scale'] ?? null,
            $options['mask'] ?? null,
            $options['maskColor'] ?? null,
        );
    }
}
