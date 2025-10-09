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

namespace Playwright\FileChooser;

use Playwright\Page\PageInterface;

/**
 * FileChooserInterface for Playwright PHP.
 */
interface FileChooserInterface
{
    /**
     * Returns input element associated with this file chooser.
     */
    public function element(): string;

    /**
     * Returns whether this file chooser accepts multiple files.
     */
    public function isMultiple(): bool;

    /**
     * Returns page this file chooser belongs to.
     */
    public function page(): PageInterface;

    /**
     * Sets the value of the file input this chooser is associated with.
     * If some of the filePaths are relative paths, then they are resolved relative to the current working directory.
     * For empty array, clears the selected files.
     *
     * @param string|array<string>|array{name: string, mimeType: string, buffer: string}|array<array{name: string, mimeType: string, buffer: string}> $files
     * @param array{noWaitAfter?: bool, timeout?: int}                                                                                                $options
     */
    public function setFiles(string|array $files, array $options = []): void;
}
