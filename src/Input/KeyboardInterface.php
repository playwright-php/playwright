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

interface KeyboardInterface
{
    /**
     * Inserts the text as-is: only an input event fires, no keydown/keyup.
     */
    public function insertText(string $text): void;

    public function down(string $key): void;

    /**
     * @param array<string, mixed> $options
     */
    public function press(string $key, array $options = []): void;

    /**
     * @param array<string, mixed> $options
     */
    public function type(string $text, array $options = []): void;

    public function up(string $key): void;
}
