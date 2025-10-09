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

use Playwright\Page\PageInterface;

/**
 * DownloadInterface for Playwright PHP.
 */
interface DownloadInterface
{
    /**
     * Cancels a download. Will not fail if the download is already finished or canceled.
     * Upon successful cancellations, download.failure() would resolve to 'canceled'.
     */
    public function cancel(): void;

    /**
     * Returns a readable stream for a successful download, or throws for a failed/canceled download.
     *
     * @return resource
     */
    public function createReadStream();

    /**
     * Deletes the downloaded file. Will wait for the download to finish if necessary.
     */
    public function delete(): void;

    /**
     * Returns download error if any. Will wait for the download to finish if necessary.
     */
    public function failure(): ?string;

    /**
     * Get the page that the download belongs to.
     */
    public function page(): PageInterface;

    /**
     * Returns path to the downloaded file for a successful download, or throws for a failed/canceled download.
     * The method will wait for the download to finish if necessary.
     */
    public function path(): string;

    /**
     * Copy the download to a user-specified path. It is safe to call this method while the download is still in progress.
     * Will wait for the download to finish if necessary.
     */
    public function saveAs(string $path): void;

    /**
     * Returns suggested filename for this download. It is typically computed by the browser from the
     * Content-Disposition response header or the download attribute.
     */
    public function suggestedFilename(): string;

    public function url(): string;
}
