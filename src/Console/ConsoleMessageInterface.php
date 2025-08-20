<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Console;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface ConsoleMessageInterface
{
    public function type(): string;

    public function text(): string;

    public function args(): array;

    public function location(): array;
}
