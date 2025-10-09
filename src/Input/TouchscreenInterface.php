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

namespace Playwright\Input;

/**
 * @see https://playwright.dev/docs/api/class-touchscreen
 */
interface TouchscreenInterface
{
    /**
     * Dispatches a touchstart and touchend event with a single touch at the position (x,y).
     */
    public function tap(float $x, float $y): void;
}
