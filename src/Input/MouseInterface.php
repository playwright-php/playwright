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
interface MouseInterface
{
    public function click(float $x, float $y, array $options = []): void;

    public function move(float $x, float $y, array $options = []): void;

    public function wheel(float $deltaX, float $deltaY): void;
}
