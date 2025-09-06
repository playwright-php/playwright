<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Input;

use PlaywrightPHP\Exception\InvalidArgumentException;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
enum ModifierKey: string
{
    case Alt = 'Alt';
    case Control = 'Control';
    case Meta = 'Meta';
    case Shift = 'Shift';

    public static function fromString(string $modifier): self
    {
        return match (strtolower($modifier)) {
            'alt' => self::Alt,
            'control', 'ctrl' => self::Control,
            'meta', 'cmd', 'command' => self::Meta,
            'shift' => self::Shift,
            default => throw new InvalidArgumentException(sprintf('Unknown modifier key: "%s".', $modifier)),
        };
    }
}
