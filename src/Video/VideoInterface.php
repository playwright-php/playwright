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
 * @see https://playwright.dev/docs/api/class-video
 */
interface VideoInterface
{
    /**
     * Deletes the video file. Will wait for the video to finish if necessary.
     */
    public function delete(): void;

    /**
     * Returns the file system path this video will be recorded to.
     */
    public function path(): string;

    /**
     * Saves the video to a user-specified path.
     *
     * @param string $path Path where the video should be saved
     */
    public function saveAs(string $path): void;
}
