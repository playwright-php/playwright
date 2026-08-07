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

final readonly class EmulateMediaOptions
{
    /**
     * An omitted feature keeps its current emulation. Pass 'no-override' to
     * drop the emulation and fall back to the browser default.
     *
     * @param 'light'|'dark'|'no-preference'|'no-override'|null $colorScheme
     * @param 'no-preference'|'more'|'no-override'|null         $contrast
     * @param 'active'|'none'|'no-override'|null                $forcedColors
     * @param 'screen'|'print'|'no-override'|null               $media
     * @param 'reduce'|'no-preference'|'no-override'|null       $reducedMotion
     */
    public function __construct(
        public ?string $colorScheme = null,
        public ?string $contrast = null,
        public ?string $forcedColors = null,
        public ?string $media = null,
        public ?string $reducedMotion = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        if (null !== $this->colorScheme) {
            $options['colorScheme'] = $this->colorScheme;
        }
        if (null !== $this->contrast) {
            $options['contrast'] = $this->contrast;
        }
        if (null !== $this->forcedColors) {
            $options['forcedColors'] = $this->forcedColors;
        }
        if (null !== $this->media) {
            $options['media'] = $this->media;
        }
        if (null !== $this->reducedMotion) {
            $options['reducedMotion'] = $this->reducedMotion;
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

        /** @var 'light'|'dark'|'no-preference'|'no-override'|null $colorScheme */
        $colorScheme = $options['colorScheme'] ?? null;
        /** @var 'no-preference'|'more'|'no-override'|null $contrast */
        $contrast = $options['contrast'] ?? null;
        /** @var 'active'|'none'|'no-override'|null $forcedColors */
        $forcedColors = $options['forcedColors'] ?? null;
        /** @var 'screen'|'print'|'no-override'|null $media */
        $media = $options['media'] ?? null;
        /** @var 'reduce'|'no-preference'|'no-override'|null $reducedMotion */
        $reducedMotion = $options['reducedMotion'] ?? null;

        return new self($colorScheme, $contrast, $forcedColors, $media, $reducedMotion);
    }
}
