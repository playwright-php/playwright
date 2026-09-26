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

namespace Playwright\Assertions\Options;

use Playwright\Locator\Locator;
use Playwright\Locator\LocatorInterface;

/**
 * Options of toHaveScreenshot(), named and defaulted like their
 * counterparts in @playwright/test.
 *
 * Use named arguments:
 *
 *     new ToHaveScreenshotOptions(maxDiffPixelRatio: 0.01, mask: [$page->locator('.date')])
 *
 * Invalid values throw an \InvalidArgumentException on construction, so a
 * typo fails in PHP before reaching the browser.
 */
final readonly class ToHaveScreenshotOptions
{
    public const COMPARATOR_PIXELMATCH = 'pixelmatch';
    public const COMPARATOR_SSIM_CIE94 = 'ssim-cie94';

    private const COMPARATORS = [self::COMPARATOR_PIXELMATCH, self::COMPARATOR_SSIM_CIE94];
    private const ANIMATIONS = ['disabled', 'allow'];
    private const CARETS = ['hide', 'initial'];
    private const SCALES = ['css', 'device'];

    /**
     * @param float                                                       $threshold         Color distance tolerated for each pixel, from 0 (exact) to 1 (anything).
     *                                                                                       Used by pixelmatch only. 0.2 forgives anti-aliasing, and also close
     *                                                                                       shades of one hue: lower it to 0.1 to catch a design token change.
     * @param int|null                                                    $maxDiffPixels     Number of differing pixels tolerated. None by default: any differing
     *                                                                                       pixel fails. When set with $maxDiffPixelRatio, the stricter one wins.
     * @param float|null                                                  $maxDiffPixelRatio Share of differing pixels tolerated, from 0 to 1. None by default.
     * @param 'pixelmatch'|'ssim-cie94'                                   $comparator        pixelmatch compares pixel colors. ssim-cie94 compares local structure
     *                                                                                       and perceived color: steadier across rendering noise, stricter on
     *                                                                                       color shifts, slower, experimental in Playwright.
     * @param list<LocatorInterface>                                      $mask              Elements covered with a solid box of $maskColor before the comparison,
     *                                                                                       for dates, avatars or ads. A locator obtained through frameLocator()
     *                                                                                       masks inside its iframe.
     * @param string                                                      $maskColor         Any CSS color. Magenta by default, a color no interface uses.
     * @param string|null                                                 $style             CSS applied while the screenshot is taken, to hide or freeze dynamic
     *                                                                                       parts. It pierces the Shadow DOM and applies to iframes.
     * @param bool                                                        $fullPage          Captures the whole scrollable page. Page assertions only.
     * @param array{x: float, y: float, width: float, height: float}|null $clip              region of the page to capture, in CSS pixels
     * @param bool                                                        $omitBackground    transparent background instead of the default white one
     * @param 'disabled'|'allow'                                          $animations        "disabled" fast-forwards finite CSS animations and transitions to their
     *                                                                                       end and cancels infinite ones, so the page can settle
     * @param 'hide'|'initial'                                            $caret             "hide" hides the text caret, which blinks
     * @param 'css'|'device'                                              $scale             "css" takes one pixel per CSS pixel, so baselines match across screen
     *                                                                                       densities. "device" is sharper and larger on high-DPI screens.
     * @param int|null                                                    $timeoutMs         Time allowed to get two identical consecutive screenshots, in
     *                                                                                       milliseconds. Defaults to the assertion timeout.
     * @param string|null                                                 $message           replaces the first line of the failure message
     */
    public function __construct(
        public float $threshold = 0.2,
        public ?int $maxDiffPixels = null,
        public ?float $maxDiffPixelRatio = null,
        public string $comparator = self::COMPARATOR_PIXELMATCH,
        public array $mask = [],
        public string $maskColor = '#FF00FF',
        public ?string $style = null,
        public bool $fullPage = false,
        public ?array $clip = null,
        public bool $omitBackground = false,
        public string $animations = 'disabled',
        public string $caret = 'hide',
        public string $scale = 'css',
        public ?int $timeoutMs = null,
        public ?string $message = null,
    ) {
        self::assertRange('threshold', $threshold);
        if (null !== $maxDiffPixelRatio) {
            self::assertRange('maxDiffPixelRatio', $maxDiffPixelRatio);
        }
        if (null !== $maxDiffPixels && $maxDiffPixels < 0) {
            throw new \InvalidArgumentException(sprintf('The maxDiffPixels option must be 0 or more, %d given.', $maxDiffPixels));
        }
        if (null !== $timeoutMs && $timeoutMs < 0) {
            throw new \InvalidArgumentException(sprintf('The timeoutMs option must be 0 or more, %d given.', $timeoutMs));
        }

        self::assertChoice('comparator', $comparator, self::COMPARATORS);
        self::assertChoice('animations', $animations, self::ANIMATIONS);
        self::assertChoice('caret', $caret, self::CARETS);
        self::assertChoice('scale', $scale, self::SCALES);

        if ('' === trim($maskColor)) {
            throw new \InvalidArgumentException('The maskColor option must be a CSS color, an empty string given.');
        }

        foreach ($mask as $index => $locator) {
            if (!$locator instanceof LocatorInterface) {
                throw new \InvalidArgumentException(sprintf('The mask option expects %s instances, %s given at index %s.', LocatorInterface::class, get_debug_type($locator), $index));
            }
        }

        if (null !== $clip) {
            self::assertClip($clip);
        }
    }

    /**
     * Options sent to the bridge. The timeout and the message are applied on
     * the PHP side and are not part of it.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = array_filter([
            'threshold' => $this->threshold,
            'maxDiffPixels' => $this->maxDiffPixels,
            'maxDiffPixelRatio' => $this->maxDiffPixelRatio,
            'comparator' => $this->comparator,
            'maskColor' => $this->maskColor,
            'style' => $this->style,
            'fullPage' => $this->fullPage,
            'clip' => $this->clip,
            'omitBackground' => $this->omitBackground,
            'animations' => $this->animations,
            'caret' => $this->caret,
            'scale' => $this->scale,
        ], static fn (mixed $value): bool => null !== $value);

        if ([] !== $this->mask) {
            $options['mask'] = array_map(
                static fn (LocatorInterface $locator): array => array_filter([
                    'selector' => $locator->getSelector(),
                    'frameSelector' => $locator instanceof Locator ? $locator->getFrameSelector() : null,
                ], static fn (?string $value): bool => null !== $value),
                $this->mask,
            );
        }

        return $options;
    }

    private static function assertRange(string $option, float $value): void
    {
        if ($value < 0 || $value > 1) {
            throw new \InvalidArgumentException(sprintf('The %s option must be between 0 and 1, %s given.', $option, $value));
        }
    }

    /**
     * @param list<string> $choices
     */
    private static function assertChoice(string $option, string $value, array $choices): void
    {
        if (!in_array($value, $choices, true)) {
            throw new \InvalidArgumentException(sprintf('The %s option must be one of "%s", "%s" given.', $option, implode('", "', $choices), $value));
        }
    }

    /**
     * @param array<mixed> $clip
     */
    private static function assertClip(array $clip): void
    {
        foreach (['x', 'y', 'width', 'height'] as $key) {
            if (!isset($clip[$key]) || !is_int($clip[$key]) && !is_float($clip[$key])) {
                throw new \InvalidArgumentException(sprintf('The clip option needs numeric "x", "y", "width" and "height" keys, "%s" is missing or not a number.', $key));
            }
        }

        if ($clip['width'] <= 0 || $clip['height'] <= 0) {
            throw new \InvalidArgumentException('The clip option needs a width and a height greater than 0.');
        }

        if ([] !== array_diff(array_keys($clip), ['x', 'y', 'width', 'height'])) {
            throw new \InvalidArgumentException(sprintf('The clip option only accepts "x", "y", "width" and "height" keys, "%s" given.', implode('", "', array_keys($clip))));
        }
    }
}
