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

namespace Playwright\Media\Screenshot;

use Playwright\Media\Filesystem\FilesystemInterface;
use Playwright\Page\Options\ScreenshotOptions;
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class Screenshot
{
    public function __construct(
        private TransportInterface $transport,
        private FilesystemInterface $filesystem,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @param array<string, mixed>|ScreenshotOptions $options
     */
    public function take(string $pageId, ?string $path, string $url, array|ScreenshotOptions $options = []): string
    {
        $options = ScreenshotOptions::from($options)->toArray();

        /** @var string $finalPath */
        $finalPath = $path ?? $options['path'] ?? $this->generateFilename($url);

        // Prevent saving the file by Playwright
        if (isset($options['path'])) {
            unset($options['path']);
        }

        $this->logger->debug('Taking screenshot', ['path' => $finalPath, 'options' => $options]);

        try {
            /** @var array{binary: ?string} $response */
            $response = $this->transport->send(
                [
                    'options' => $options,
                    'action' => 'page.screenshot',
                    'pageId' => $pageId,
                ],
            );

            $finalPath = $this->filesystem->write($finalPath, base64_decode($response['binary'] ?? ''));
        } catch (\Throwable $e) {
            $this->logger->error('Failed to take screenshot', [
                'path' => $finalPath,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            throw $e;
        }

        $this->logger->info('Screenshot saved successfully', ['path' => $finalPath]);

        return $finalPath;
    }

    private function generateFilename(string $url): string
    {
        $now = microtime(true);
        $datetime = date('Ymd_His', (int) $now);
        $milliseconds = sprintf('%03d', ($now - floor($now)) * 1000);

        $urlSlug = ScreenshotHelper::slugifyUrl($url);
        $safeExtension = ltrim('png', '.');

        return sprintf('%s_%s_%s.%s', $datetime, $milliseconds, $urlSlug, $safeExtension ?: 'png');
    }
}
