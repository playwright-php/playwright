<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Support;

/**
 * Utility for generating screenshot filenames and managing screenshot directories.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ScreenshotHelper
{
    /**
     * Generate an auto screenshot filename based on current datetime and URL.
     *
     * Format: YYYYMMDD_HHMMSS_mmm_url-slug.png
     * Example: 20240811_143052_123_github-com-smnandre.png
     */
    public static function generateFilename(string $url, string $directory): string
    {
        $now = microtime(true);
        $datetime = date('Ymd_His', (int) $now);
        $milliseconds = sprintf('%03d', ($now - floor($now)) * 1000);

        $urlSlug = self::slugifyUrl($url, 40);

        $filename = sprintf('%s_%s_%s.png', $datetime, $milliseconds, $urlSlug);

        self::ensureDirectoryExists($directory);

        return $directory.DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * Slugify a URL for use in filenames.
     *
     * @param string $url       The URL to slugify
     * @param int    $maxLength Maximum length of the slug
     *
     * @return string The slugified URL
     */
    public static function slugifyUrl(string $url, int $maxLength = 40): string
    {
        $slug = preg_replace('/^https?:\/\//', '', $url);

        if (is_string($slug)) {
            $slug = preg_replace('/^www\./', '', $slug);
        }
        if (null === $slug) {
            $slug = 'invalid-url';
        }

        $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', $slug);
        if (null === $slug) {
            $slug = 'invalid-url';
        }

        $slug = trim($slug, '-');

        $slug = strtolower($slug);

        if (strlen($slug) > $maxLength) {
            $slug = substr($slug, 0, $maxLength);

            $slug = rtrim($slug, '-');
        }

        if (empty($slug)) {
            $slug = 'screenshot';
        }

        return $slug;
    }

    /**
     * Ensure a directory exists, creating it if necessary.
     *
     * @param string $directory The directory path
     *
     * @throws \RuntimeException If directory cannot be created
     */
    public static function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Failed to create screenshot directory: %s', $directory));
        }
    }

    /**
     * Clean up old screenshots in a directory.
     *
     * @param string $directory The directory to clean
     * @param int    $maxAge    Maximum age in seconds (default: 7 days)
     * @param int    $maxFiles  Maximum number of files to keep (default: 100)
     */
    public static function cleanupOldScreenshots(string $directory, int $maxAge = 604800, int $maxFiles = 100): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $files = [];
        $currentTime = time();
        $cleanedCount = 0;

        foreach (new \DirectoryIterator($directory) as $fileInfo) {
            if ($fileInfo->isDot() || 'png' !== $fileInfo->getExtension()) {
                continue;
            }

            $filePath = $fileInfo->getPathname();
            $modTime = $fileInfo->getMTime();

            if (($currentTime - $modTime) > $maxAge) {
                if (unlink($filePath)) {
                    ++$cleanedCount;
                }
                continue;
            }

            $files[] = [
                'path' => $filePath,
                'mtime' => $modTime,
            ];
        }

        if (count($files) > $maxFiles) {
            usort($files, fn ($a, $b) => $a['mtime'] <=> $b['mtime']);

            $filesToDelete = array_slice($files, 0, count($files) - $maxFiles);
            foreach ($filesToDelete as $file) {
                if (unlink($file['path'])) {
                    ++$cleanedCount;
                }
            }
        }

        return $cleanedCount;
    }

    /**
     * Get information about screenshots in a directory.
     *
     * @param string $directory The directory to analyze
     *
     * @return array{count: int, totalSize: int, oldestFile: string|null, newestFile: string|null}
     */
    public static function getDirectoryInfo(string $directory): array
    {
        $info = [
            'count' => 0,
            'totalSize' => 0,
            'oldestFile' => null,
            'newestFile' => null,
            'oldestTime' => null,
            'newestTime' => null,
        ];

        if (!is_dir($directory)) {
            unset($info['oldestTime'], $info['newestTime']);

            return $info;
        }

        foreach (new \DirectoryIterator($directory) as $fileInfo) {
            if ($fileInfo->isDot() || 'png' !== $fileInfo->getExtension()) {
                continue;
            }

            ++$info['count'];
            $info['totalSize'] += $fileInfo->getSize();

            $mtime = $fileInfo->getMTime();

            if (null === $info['oldestTime'] || $mtime < $info['oldestTime']) {
                $info['oldestTime'] = $mtime;
                $info['oldestFile'] = $fileInfo->getFilename();
            }

            if (null === $info['newestTime'] || $mtime > $info['newestTime']) {
                $info['newestTime'] = $mtime;
                $info['newestFile'] = $fileInfo->getFilename();
            }
        }

        unset($info['oldestTime'], $info['newestTime']);

        return $info;
    }
}
