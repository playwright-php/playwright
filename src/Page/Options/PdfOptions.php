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

use Playwright\Exception\RuntimeException;

/**
 * @phpstan-type PdfMargins array{top?: string, right?: string, bottom?: string, left?: string}
 * @phpstan-type PdfOptionsArray array{
 *     path?: string,
 *     format?: string,
 *     landscape?: bool,
 *     scale?: float,
 *     printBackground?: bool,
 *     width?: string,
 *     height?: string,
 *     margin?: PdfMargins,
 *     displayHeaderFooter?: bool,
 *     footerTemplate?: string,
 *     headerTemplate?: string,
 *     outline?: bool,
 *     pageRanges?: string,
 *     preferCSSPageSize?: bool,
 *     tagged?: bool
 * }
 */
final class PdfOptions
{
    private const SCALE_MIN = 0.1;
    private const SCALE_MAX = 2.0;

    private ?string $path;
    private ?string $format;
    private ?bool $landscape;
    private ?float $scale;
    private ?bool $printBackground;
    private ?string $width;
    private ?string $height;
    /** @var PdfMargins|null */
    private ?array $margin;
    private ?bool $displayHeaderFooter;
    private ?string $footerTemplate;
    private ?string $headerTemplate;
    private ?bool $outline;
    private ?string $pageRanges;
    private ?bool $preferCSSPageSize;
    private ?bool $tagged;

    public function __construct(
        ?string $path = null,
        ?string $format = null,
        ?bool $landscape = null,
        ?float $scale = null,
        ?bool $printBackground = null,
        ?string $width = null,
        ?string $height = null,
        mixed $margin = null,
        ?bool $displayHeaderFooter = null,
        ?string $footerTemplate = null,
        ?string $headerTemplate = null,
        ?bool $outline = null,
        ?string $pageRanges = null,
        ?bool $preferCSSPageSize = null,
        ?bool $tagged = null,
    ) {
        $this->path = self::normalizeNullableString($path);
        $this->format = self::normalizeNullableString($format);
        $this->landscape = $landscape;
        $this->scale = null;
        if (null !== $scale) {
            if ($scale < self::SCALE_MIN || $scale > self::SCALE_MAX) {
                throw new RuntimeException(sprintf('PDF scale must be between %.1f and %.1f.', self::SCALE_MIN, self::SCALE_MAX));
            }

            $this->scale = round($scale, 2);
        }

        $this->printBackground = $printBackground;
        $this->width = self::normalizeNullableString($width);
        $this->height = self::normalizeNullableString($height);
        $this->margin = self::normalizeMargin($margin);
        $this->displayHeaderFooter = $displayHeaderFooter;
        $this->footerTemplate = self::normalizeNullableString($footerTemplate);
        $this->headerTemplate = self::normalizeNullableString($headerTemplate);
        $this->outline = $outline;
        $this->pageRanges = self::normalizeNullableString($pageRanges);
        $this->preferCSSPageSize = $preferCSSPageSize;
        $this->tagged = $tagged;
    }

    /**
     * @param array<string, mixed>|self $options
     */
    public static function from(array|self $options): self
    {
        if ($options instanceof self) {
            return $options;
        }

        return self::fromArray($options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function fromArray(array $options): self
    {
        $scale = null;
        if (array_key_exists('scale', $options)) {
            if (!is_numeric($options['scale'])) {
                throw new RuntimeException('PDF option "scale" must be numeric.');
            }

            $scale = (float) $options['scale'];
        }

        $path = self::extractString($options, 'path');
        $format = self::extractString($options, 'format');
        $width = self::extractString($options, 'width');
        $height = self::extractString($options, 'height');
        $footerTemplate = self::extractString($options, 'footerTemplate');
        $headerTemplate = self::extractString($options, 'headerTemplate');
        $pageRanges = self::extractString($options, 'pageRanges');

        $landscape = self::extractBool($options, 'landscape');
        $printBackground = self::extractBool($options, 'printBackground');
        $displayHeaderFooter = self::extractBool($options, 'displayHeaderFooter');
        $outline = self::extractBool($options, 'outline');
        $preferCSSPageSize = self::extractBool($options, 'preferCSSPageSize');
        $tagged = self::extractBool($options, 'tagged');

        return new self(
            path: $path,
            format: $format,
            landscape: $landscape,
            scale: $scale,
            printBackground: $printBackground,
            width: $width,
            height: $height,
            margin: $options['margin'] ?? null,
            displayHeaderFooter: $displayHeaderFooter,
            footerTemplate: $footerTemplate,
            headerTemplate: $headerTemplate,
            outline: $outline,
            pageRanges: $pageRanges,
            preferCSSPageSize: $preferCSSPageSize,
            tagged: $tagged,
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function extractString(array $options, string $key): ?string
    {
        if (!isset($options[$key])) {
            return null;
        }

        $value = $options[$key];

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function extractBool(array $options, string $key): ?bool
    {
        if (!isset($options[$key])) {
            return null;
        }

        return (bool) $options[$key];
    }

    public function path(): ?string
    {
        return $this->path;
    }

    public function withPath(?string $path): self
    {
        return new self(
            path: $path,
            format: $this->format,
            landscape: $this->landscape,
            scale: $this->scale,
            printBackground: $this->printBackground,
            width: $this->width,
            height: $this->height,
            margin: $this->margin,
            displayHeaderFooter: $this->displayHeaderFooter,
            footerTemplate: $this->footerTemplate,
            headerTemplate: $this->headerTemplate,
            outline: $this->outline,
            pageRanges: $this->pageRanges,
            preferCSSPageSize: $this->preferCSSPageSize,
            tagged: $this->tagged,
        );
    }

    /**
     * @return PdfOptionsArray
     */
    public function toArray(): array
    {
        $options = [];

        if (null !== $this->path) {
            $options['path'] = $this->path;
        }
        if (null !== $this->format) {
            $options['format'] = $this->format;
        }
        if (null !== $this->landscape) {
            $options['landscape'] = $this->landscape;
        }
        if (null !== $this->scale) {
            $options['scale'] = $this->scale;
        }
        if (null !== $this->printBackground) {
            $options['printBackground'] = $this->printBackground;
        }
        if (null !== $this->width) {
            $options['width'] = $this->width;
        }
        if (null !== $this->height) {
            $options['height'] = $this->height;
        }
        if (null !== $this->margin) {
            $options['margin'] = $this->margin;
        }
        if (null !== $this->displayHeaderFooter) {
            $options['displayHeaderFooter'] = $this->displayHeaderFooter;
        }
        if (null !== $this->footerTemplate) {
            $options['footerTemplate'] = $this->footerTemplate;
        }
        if (null !== $this->headerTemplate) {
            $options['headerTemplate'] = $this->headerTemplate;
        }
        if (null !== $this->outline) {
            $options['outline'] = $this->outline;
        }
        if (null !== $this->pageRanges) {
            $options['pageRanges'] = $this->pageRanges;
        }
        if (null !== $this->preferCSSPageSize) {
            $options['preferCSSPageSize'] = $this->preferCSSPageSize;
        }
        if (null !== $this->tagged) {
            $options['tagged'] = $this->tagged;
        }

        return $options;
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $trimmed = trim($value);

        return '' === $trimmed ? null : $trimmed;
    }

    /**
     * @return PdfMargins|null
     */
    private static function normalizeMargin(mixed $margin): ?array
    {
        if (null === $margin) {
            return null;
        }

        if (!is_array($margin)) {
            throw new RuntimeException('PDF option "margin" must be an array of edge => size.');
        }

        $normalized = [];
        foreach (['top', 'right', 'bottom', 'left'] as $edge) {
            if (!array_key_exists($edge, $margin)) {
                continue;
            }

            $value = $margin[$edge];
            if (null === $value) {
                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $stringValue = (string) $value;
            $normalizedValue = self::normalizeNullableString($stringValue);
            if (null !== $normalizedValue) {
                $normalized[$edge] = $normalizedValue;
            }
        }

        return [] === $normalized ? null : $normalized;
    }
}
