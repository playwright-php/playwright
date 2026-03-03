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

namespace Playwright\Locator;

use Playwright\Regex;

/**
 * Helper for generating Playwright role selectors with accessibility focused options.
 *
 * @internal
 */
final class RoleSelectorBuilder
{
    /** @var array<int, string> */
    private const ROLE_SPECIFIC_KEYS = [
        'name',
        'exact',
        'checked',
        'disabled',
        'expanded',
        'includeHidden',
        'level',
        'pressed',
        'selected',
    ];

    /**
     * @param array<string, mixed> $options
     */
    public static function buildSelector(string $role, array $options = []): string
    {
        $normalizedRole = self::normalizeRole($role);
        $selector = 'internal:role='.$normalizedRole;

        $nameFragment = self::buildNameAttribute($options, !empty($options['exact']));
        if (null !== $nameFragment) {
            $selector .= $nameFragment;
        }

        $selector .= self::buildBooleanAttribute('checked', $options['checked'] ?? null);
        $selector .= self::buildBooleanAttribute('disabled', $options['disabled'] ?? null);
        $selector .= self::buildBooleanAttribute('expanded', $options['expanded'] ?? null);
        $selector .= self::buildPressedAttribute($options['pressed'] ?? null);
        $selector .= self::buildBooleanAttribute('selected', $options['selected'] ?? null);

        $includeHidden = $options['includeHidden'] ?? null;
        if (true === $includeHidden) {
            $selector .= '[include-hidden]';
        }

        $level = $options['level'] ?? null;
        if (is_int($level) && $level > 0) {
            $selector .= '[level='.$level.']';
        }

        return $selector;
    }

    /**
     * Remove role-specific options so the remainder can be forwarded to Locator options (has, hasText...).
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    public static function filterLocatorOptions(array $options): array
    {
        return array_diff_key($options, array_flip(self::ROLE_SPECIFIC_KEYS));
    }

    private static function normalizeRole(string $role): string
    {
        $trimmed = trim($role);

        return '' === $trimmed ? '' : strtolower($trimmed);
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function buildNameAttribute(array $options, bool $exact = false): ?string
    {
        if (!array_key_exists('name', $options)) {
            return null;
        }

        $nameOption = $options['name'];

        if ($nameOption instanceof Regex) {
            return '[name='.$nameOption->pattern.']';
        }

        if ($nameOption instanceof \Stringable) {
            $nameOption = (string) $nameOption;
        }

        if (is_string($nameOption)) {
            $nameOption = trim($nameOption);
            if ('' === $nameOption) {
                return null;
            }

            if ($exact) {
                return '[name="'.self::escapeAttributeValue($nameOption).'"]';
            }

            return '[name=/'.preg_quote($nameOption, '/').'/i]';
        }

        return null;
    }

    private static function buildBooleanAttribute(string $attribute, mixed $value): string
    {
        if (!is_bool($value)) {
            return '';
        }

        $name = self::attributeName($attribute);

        return $value ? '['.$name.']' : '['.$name.'=false]';
    }

    private static function buildPressedAttribute(mixed $value): string
    {
        if (is_string($value) && 'mixed' === strtolower(trim($value))) {
            return '[pressed="mixed"]';
        }

        return self::buildBooleanAttribute('pressed', $value);
    }

    private static function attributeName(string $optionName): string
    {
        return match ($optionName) {
            'includeHidden' => 'include-hidden',
            default => $optionName,
        };
    }

    private static function escapeAttributeValue(string $value): string
    {
        return addcslashes($value, '\\"');
    }
}
