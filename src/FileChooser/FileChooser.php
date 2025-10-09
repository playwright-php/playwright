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
use Playwright\Transport\TransportInterface;

/**
 * FileChooser class for Playwright PHP.
 */
final class FileChooser implements FileChooserInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly PageInterface $page,
        private readonly string $elementId,
        private readonly array $data = [],
    ) {
    }

    public function element(): mixed
    {
        return $this->data['element'] ?? null;
    }

    public function isMultiple(): bool
    {
        return (bool) ($this->data['isMultiple'] ?? false);
    }

    public function page(): PageInterface
    {
        return $this->page;
    }

    /**
     * @param string|array<string>|array{name: string, mimeType: string, buffer: string}|array<array{name: string, mimeType: string, buffer: string}> $files
     * @param array{noWaitAfter?: bool, timeout?: int}                                                                                                $options
     */
    public function setFiles(string|array $files, array $options = []): void
    {
        $normalizedFiles = $this->normalizeFiles($files);

        $payload = [
            'action' => 'fileChooser.setFiles',
            'elementId' => $this->elementId,
            'files' => $normalizedFiles,
        ];

        if (!empty($options)) {
            $payload['options'] = $options;
        }

        $fileChooserId = $this->data['fileChooserId'] ?? null;
        if (is_string($fileChooserId) && '' !== $fileChooserId) {
            $payload['fileChooserId'] = $fileChooserId;
        }

        $this->transport->send($payload);
    }

    /**
     * @param string|array<string>|array{name: string, mimeType: string, buffer: string}|array<array{name: string, mimeType: string, buffer: string}> $files
     *
     * @return array<array{name: string, mimeType: string, buffer: string}|string>
     */
    private function normalizeFiles(string|array $files): array
    {
        if (is_string($files)) {
            return [$files];
        }
        if (isset($files['name'], $files['mimeType'], $files['buffer'])
            && is_string($files['name'])
            && is_string($files['mimeType'])
            && is_string($files['buffer'])
        ) {
            /* @var array{name: string, mimeType: string, buffer: string} $files */
            return [$files];
        }
        $list = array_values($files);
        $out = [];
        foreach ($list as $item) {
            if (is_string($item)) {
                $out[] = $item;
                continue;
            }
            if (is_array($item)
                && isset($item['name'], $item['mimeType'], $item['buffer'])
                && is_string($item['name'])
                && is_string($item['mimeType'])
                && is_string($item['buffer'])
            ) {
                /* @var array{name: string, mimeType: string, buffer: string} $item */
                $out[] = $item;
            }
        }

        return $out;
    }
}
