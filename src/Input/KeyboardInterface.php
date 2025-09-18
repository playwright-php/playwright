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
 * @author Simon André <smn.andre@gmail.com>
 */
interface KeyboardInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function press(string $key, array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function type(string $text, array $options = []): void;
}
