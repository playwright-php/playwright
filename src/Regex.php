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

namespace Playwright;

readonly class Regex
{
    /** @see Supported JavaScript Regex flags: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_expressions#advanced_searching_with_flags */
    private const VALID_JS_FLAGS = ['d', 'g', 'i', 'm', 's', 'u', 'v', 'y'];

    private const JS_ONLY_FLAGS = ['d', 'g', 'y'];

    private const DELIMITER = '/';

    public function __construct(
        public string $pattern,
    ) {
        $this->validate($pattern);
    }

    private function validate(string $pattern): void
    {
        if ('' === $pattern || self::DELIMITER !== $pattern[0]) {
            throw new \InvalidArgumentException(sprintf('Regex pattern must start with a "%s" delimiter.', self::DELIMITER));
        }

        $lastSlash = strrpos($pattern, self::DELIMITER);
        if (false === $lastSlash || 0 === $lastSlash) {
            throw new \InvalidArgumentException(sprintf('Regex pattern must have a closing "%s" delimiter.', self::DELIMITER));
        }

        $flags = substr($pattern, $lastSlash + 1);
        if ('' !== $flags) {
            foreach (str_split($flags) as $flag) {
                if (!in_array($flag, self::VALID_JS_FLAGS, true)) {
                    throw new \InvalidArgumentException(sprintf('Invalid regex flag "%s". Valid flags are: %s.', $flag, implode(', ', self::VALID_JS_FLAGS)));
                }
            }
        }

        $body = substr($pattern, 1, $lastSlash - 1);
        $pcreFlags = array_diff(str_split($flags), self::JS_ONLY_FLAGS);
        $pcrePattern = self::DELIMITER.$body.self::DELIMITER.implode('', $pcreFlags);

        if (false === @preg_match($pcrePattern, '')) {
            throw new \InvalidArgumentException(sprintf('Invalid regex pattern: %s.', preg_last_error_msg()));
        }
    }
}
