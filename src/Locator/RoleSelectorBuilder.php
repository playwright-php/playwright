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
        'nameRegex',
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

        $nameFragment = self::buildNameAttribute($options);
        if (null !== $nameFragment) {
            $selector .= $nameFragment;
        }

        if (!empty($options['exact'])) {
            $selector .= '[exact]';
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
    private static function buildNameAttribute(array $options): ?string
    {
        if (array_key_exists('nameRegex', $options)) {
            $regexFragment = self::formatRegexAttribute('name', $options['nameRegex']);
            if (null !== $regexFragment) {
                return $regexFragment;
            }
        }

        if (!array_key_exists('name', $options)) {
            return null;
        }

        $nameOption = $options['name'];

        if (is_array($nameOption) && array_key_exists('regex', $nameOption)) {
            return self::formatRegexAttribute('name', $nameOption);
        }

        if ($nameOption instanceof \Stringable) {
            return '[name="'.self::escapeAttributeValue((string) $nameOption).'"]';
        }

        if (is_string($nameOption)) {
            $nameOption = trim($nameOption);
            if ('' === $nameOption) {
                return null;
            }

            return '[name="'.self::escapeAttributeValue($nameOption).'"]';
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

    private static function escapeRegexPattern(string $pattern): string
    {
        return addcslashes($pattern, '/');
    }

    private static function formatRegexAttribute(string $attribute, mixed $value): ?string
    {
        $pattern = null;
        $flags = '';

        if (is_string($value) || $value instanceof \Stringable) {
            $pattern = (string) $value;
        } elseif (is_array($value)) {
            $patternValue = $value['pattern'] ?? $value['regex'] ?? null;
            if (is_string($patternValue) || $patternValue instanceof \Stringable) {
                $pattern = (string) $patternValue;
            }

            $flagsValue = $value['flags'] ?? null;
            if (is_string($flagsValue)) {
                $flags = $flagsValue;
            }

            $ignoreCase = $value['ignoreCase'] ?? $value['ignore_case'] ?? null;
            if (true === $ignoreCase && !str_contains($flags, 'i')) {
                $flags .= 'i';
            }
        }

        if (null === $pattern) {
            return null;
        }

        $pattern = trim($pattern);
        if ('' === $pattern) {
            return null;
        }

        if ('/' !== $pattern[0]) {
            $pattern = '/'.self::escapeRegexPattern($pattern).'/';
        }

        if ('' !== $flags) {
            $pattern .= self::sanitizeRegexFlags($flags);
        }

        return '['.$attribute.'='.$pattern.']';
    }

    private static function sanitizeRegexFlags(string $flags): string
    {
        $valid = ['d', 'g', 'i', 'm', 's', 'u', 'y'];
        $unique = [];

        foreach (str_split($flags) as $flag) {
            if (!in_array($flag, $valid, true)) {
                continue;
            }
            if (in_array($flag, $unique, true)) {
                continue;
            }
            $unique[] = $flag;
        }

        return implode('', $unique);
    }
}
