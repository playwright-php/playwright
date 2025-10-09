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

interface MouseInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function click(float $x, float $y, array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function dblclick(float $x, float $y, array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function down(array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function move(float $x, float $y, array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function up(array $options = []): void;

    public function wheel(float $deltaX, float $deltaY): void;
}
