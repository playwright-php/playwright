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
use Playwright\Screenshot\ScreenshotMask;

class LocatorScreenshotOptions
{
    /**
     * @param 'png'|'jpeg'|null           $type
     * @param 'disabled'|'allow'|null     $animations
     * @param 'hide'|'initial'|null       $caret
     * @param 'css'|'device'|null         $scale
     * @param list<LocatorInterface>|null $mask       Elements covered with a solid box of $maskColor. A locator obtained
     *                                                through frameLocator() masks inside its iframe.
     */
    public function __construct(
        public ?string $path = null,
        public ?string $type = null,
        public ?int $quality = null,
        public ?bool $omitBackground = null,
        public ?float $timeout = null,
        public ?string $animations = null,
        public ?string $caret = null,
        public ?string $scale = null,
        public ?array $mask = null,
        public ?string $maskColor = null,
        public ?string $style = null,
    ) {
        if (null !== $mask) {
            ScreenshotMask::assertLocators($mask);
        }
    }

    /**
     * @return array<string, mixed>
     */
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
            ScreenshotMask::assertLocators($this->mask);
            $options['mask'] = ScreenshotMask::toArray($this->mask);
        }
        if (null !== $this->maskColor) {
            $options['maskColor'] = $this->maskColor;
        }
        if (null !== $this->style) {
            $options['style'] = $this->style;
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

        /** @var string|null $path */
        $path = $options['path'] ?? null;
        /** @var 'png'|'jpeg'|null $type */
        $type = $options['type'] ?? null;
        /** @var int|null $quality */
        $quality = $options['quality'] ?? null;
        /** @var bool|null $omitBackground */
        $omitBackground = $options['omitBackground'] ?? null;
        /** @var float|null $timeout */
        $timeout = $options['timeout'] ?? null;
        /** @var 'disabled'|'allow'|null $animations */
        $animations = $options['animations'] ?? null;
        /** @var 'hide'|'initial'|null $caret */
        $caret = $options['caret'] ?? null;
        /** @var 'css'|'device'|null $scale */
        $scale = $options['scale'] ?? null;
        /** @var array<mixed>|null $mask */
        $mask = $options['mask'] ?? null;
        if (null !== $mask) {
            ScreenshotMask::assertLocators($mask);
            $mask = array_values($mask);
        }
        /** @var string|null $maskColor */
        $maskColor = $options['maskColor'] ?? null;
        /** @var string|null $style */
        $style = $options['style'] ?? null;

        return new self($path, $type, $quality, $omitBackground, $timeout, $animations, $caret, $scale, $mask, $maskColor, $style);
    }
}
