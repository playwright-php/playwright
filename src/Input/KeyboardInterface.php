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
 * Sends keyboard input to a page.
 *
 * Operations target the focused element in the associated page.
 * Key combinations and text entry use different browser event sequences.
 */
interface KeyboardInterface
{
    /**
     * Inserts the text as-is: only an input event fires, no keydown/keyup.
     */
    public function insertText(string $text): void;

    /**
     * Presses a key.
     *
     * The key remains pressed until a matching up() call.
     * Modifier keys affect subsequent keyboard operations while held.
     */
    public function down(string $key): void;

    /**
     * Presses and releases a key.
     *
     * Supports key combinations such as Control+S through the key argument.
     * Options configure the delay and other browser input behavior.
     *
     * @param array<string, mixed> $options
     */
    public function press(string $key, array $options = []): void;

    /**
     * Types text character by character.
     *
     * Browser key events are emitted for each typed character.
     * Options can configure the delay between those events.
     *
     * @param array<string, mixed> $options
     */
    public function type(string $text, array $options = []): void;

    /**
     * Releases a key.
     *
     * The key must have been pressed by down() or a key combination.
     * Releasing a modifier restores normal keyboard input behavior.
     */
    public function up(string $key): void;
}
