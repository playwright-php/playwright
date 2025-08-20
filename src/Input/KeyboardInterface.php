<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Input;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface KeyboardInterface
{
    public function press(string $key, array $options = []): void;

    public function type(string $text, array $options = []): void;
}
