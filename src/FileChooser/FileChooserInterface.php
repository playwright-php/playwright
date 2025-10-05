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

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface FileChooserInterface
{
    public function element(): mixed;

    public function isMultiple(): bool;

    public function page(): \Playwright\Page\PageInterface;

    /**
     * @param string|string[]|array{name: string, mimeType: string, buffer: string}|array<array{name: string, mimeType: string, buffer: string}> $files
     * @param array<string, mixed>                                                                                                               $options
     */
    public function setFiles(string|array $files, array $options = []): void;
}
