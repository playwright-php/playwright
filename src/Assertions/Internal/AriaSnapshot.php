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

namespace Playwright\Assertions\Internal;

final class AriaSnapshot
{
    /**
     * Strips the formatting an ARIA snapshot carries no meaning in: trailing
     * whitespace, blank lines, and the indentation shared by every line.
     *
     * Indentation relative to that shared prefix is kept, since it is what
     * expresses the tree structure.
     */
    public static function normalize(string $snapshot): string
    {
        $lines = [];
        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $snapshot)) as $line) {
            $line = rtrim($line);
            if ('' !== $line) {
                $lines[] = $line;
            }
        }

        $shared = \PHP_INT_MAX;
        foreach ($lines as $line) {
            $shared = min($shared, \strlen($line) - \strlen(ltrim($line)));
        }

        return implode("\n", array_map(static fn (string $line): string => substr($line, $shared), $lines));
    }
}
