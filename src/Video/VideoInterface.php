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

namespace Playwright\Video;

/**
 * Represents a recorded page video.
 *
 * A video becomes available when its owning page or context finishes recording.
 * Save or delete its local file after the browser has completed the recording.
 *
 * @see https://playwright.dev/docs/api/class-video
 */
interface VideoInterface
{
    /**
     * Deletes the recording file.
     *
     * Waits for the browser to finish writing the video when necessary.
     * The video cannot be saved from this object after deletion.
     */
    public function delete(): void;

    /**
     * Returns the recording path.
     *
     * The path identifies the local file assigned to this video.
     * The file may not exist until the browser finishes recording.
     */
    public function path(): string;

    /**
     * Saves the recording to a path.
     *
     * Waits for the browser to finish writing the source video when necessary.
     * Creates the target directory when the implementation can do so.
     *
     * @param string $path Target file path
     */
    public function saveAs(string $path): void;
}
