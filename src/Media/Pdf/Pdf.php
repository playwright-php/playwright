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

namespace Playwright\Media\Pdf;

use Playwright\Exception\RuntimeException;
use Playwright\Media\Filesystem\FilesystemInterface;
use Playwright\Media\Screenshot\ScreenshotHelper;
use Playwright\Page\Options\PdfOptions;
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class Pdf
{
    public function __construct(
        private TransportInterface $transport,
        private FilesystemInterface $filesystem,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @param array<string, mixed>|PdfOptions $options
     */
    public function take(string $pageId, ?string $path, string $url, array|PdfOptions $options = []): string
    {
        $options = PdfOptions::from($options);

        $finalPath = $options->path() ?: '';
        $finalPath .= $path;

        if (null === $path) {
            $finalPath .= $this->generateFilename($url);
        }

        if (null !== $options->path()) {
            $options = $options->withPath(null);
        }

        $this->logger->debug('Generating PDF', ['path' => $finalPath, 'options' => $options->toArray()]);

        try {
            $finalPath = $this->filesystem->write($finalPath, $this->content($pageId, $options));
        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate PDF', [
                'path' => $finalPath,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            throw $e;
        }

        $this->logger->info('PDF saved successfully', ['path' => $finalPath]);

        return $finalPath;
    }

    /**
     * @param array<string, mixed>|PdfOptions $options
     */
    public function content(string $pageId, array|PdfOptions $options = []): string
    {
        $options = PdfOptions::from($options);

        if (null !== $options->path()) {
            throw new RuntimeException('Do not provide a "path" option when requesting inline PDF content.');
        }

        try {
            /** @var array{binary: ?string} $response */
            $response = $this->transport->send(
                array_merge($options->toArray(), [
                    'action' => 'page.pdf',
                    'pageId' => $pageId,
                ]),
            );

            return base64_decode($response['binary'] ?? throw new \RuntimeException('Failed to retrieve PDF content'));
        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate PDF', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    private function generateFilename(string $url): string
    {
        $now = microtime(true);
        $datetime = date('Ymd_His', (int) $now);
        $milliseconds = sprintf('%03d', ($now - floor($now)) * 1000);

        $urlSlug = ScreenshotHelper::slugifyUrl($url);
        $safeExtension = ltrim('pdf', '.');

        return sprintf('%s_%s_%s.%s', $datetime, $milliseconds, $urlSlug, $safeExtension ?: 'pdf');
    }
}
