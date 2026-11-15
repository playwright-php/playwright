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

/** @internal */
final class LiteralSelector
{
    public static function css(string $value): string
    {
        if (str_contains($value, "\0")) {
            throw new \InvalidArgumentException('CSS selector values cannot contain null bytes.');
        }

        return '"'.str_replace(
            ['\\', '"', "\n", "\r", "\f"],
            ['\\\\', '\\"', '\\a ', '\\d ', '\\c '],
            $value,
        ).'"';
    }

    public static function text(string $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
