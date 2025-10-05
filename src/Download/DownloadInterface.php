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

namespace Playwright\Download;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface DownloadInterface
{
    public function cancel(): void;

    public function createReadStream(): mixed;

    public function delete(): void;

    public function failure(): ?string;

    public function page(): \Playwright\Page\PageInterface;

    public function path(): ?string;

    public function saveAs(string $path): void;

    public function suggestedFilename(): string;

    public function url(): string;
}
