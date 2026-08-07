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

namespace Playwright\Screencast;

/**
 * Records a video of a page and draws annotations over it.
 *
 * The overlay and action methods work whether or not a recording is running,
 * so they can also be used to decorate a page on their own.
 *
 * @see https://playwright.dev/docs/api/class-screencast
 */
interface ScreencastInterface
{
    /**
     * Stops decorating actions and removes the decorations already on screen.
     */
    public function hideActions(): void;

    /**
     * Stops rendering the overlays without discarding them, so showOverlays() can bring them back.
     */
    public function hideOverlays(): void;

    /**
     * Decorates every later action with a cursor and a title, until hideActions() is called.
     *
     * @param array{cursor?: 'none'|'pointer', duration?: int, fontSize?: int, position?: 'top-left'|'top'|'top-right'|'bottom-left'|'bottom'|'bottom-right'} $options
     */
    public function showActions(array $options = []): void;

    /**
     * Shows a title card over a blurred backdrop, removed once its duration elapses.
     *
     * @param array{description?: string, duration?: int} $options
     */
    public function showChapter(string $title, array $options = []): void;

    /**
     * Draws HTML over the page; without a duration the overlay stays until hideOverlays().
     *
     * @param array{duration?: int} $options
     */
    public function showOverlay(string $html, array $options = []): void;

    /**
     * Renders the overlays that hideOverlays() had suppressed.
     */
    public function showOverlays(): void;

    /**
     * Starts recording; without a path the frames are captured but never written anywhere.
     *
     * Throws when a screencast is already running on the page.
     *
     * @param array{path?: string, size?: array{width: int, height: int}, quality?: int, annotate?: array{duration?: int, fontSize?: int, position?: 'top-left'|'top'|'top-right'|'bottom-left'|'bottom'|'bottom-right'}} $options
     */
    public function start(array $options = []): void;

    /**
     * Writes the video to the path given to start(); does nothing when no screencast is running.
     */
    public function stop(): void;
}
