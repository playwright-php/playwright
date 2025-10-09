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
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly PageInterface $page,
        private readonly string $fileChooserId,
        private readonly string $elementId,
        private readonly bool $isMultiple,
    ) {
    }

    public function element(): string
    {
        return $this->elementId;
 * @author Simon André <smn.andre@gmail.com>
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
        private readonly array $data,
    ) {
    }

    public function element(): mixed
    {
        // TODO: ElementHandle implementation not yet available
        return null;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
        return (bool) ($this->data['isMultiple'] ?? false);
    }

    public function page(): PageInterface
    {
        return $this->page;
    }

    /**
     * @param string|array<string>|array{name: string, mimeType: string, buffer: string}|array<array{name: string,
     *                                                 mimeType: string, buffer: string}> $files
     * @param array{noWaitAfter?: bool, timeout?: int} $options
     */
    public function setFiles(string|array $files, array $options = []): void
    {
        $normalizedFiles = $this->normalizeFiles($files);

        $this->transport->send([
            'action' => 'fileChooserSetFiles',
            'fileChooserId' => $this->fileChooserId,
            'files' => $normalizedFiles,
            'options' => $options,
        ]);
    }

    /**
     * @param string|array<string>|array{name: string, mimeType: string, buffer: string}|array<array{name: string,
     *                                                 mimeType: string, buffer: string}> $files
     *
     * @return array<array{name: string, mimeType: string, buffer: string}|string>
     */
    private function normalizeFiles(string|array $files): array
    {
        if (is_string($files)) {
            return [$files];
        }

        // Single file object
        if (isset($files['name'], $files['mimeType'], $files['buffer'])
            && is_string($files['name'])
            && is_string($files['mimeType'])
            && is_string($files['buffer'])
        ) {
            /* @var array{name: string, mimeType: string, buffer: string} $files */
            return [$files];
        }

        // At this point $files is an array list: either array<string> or array<array{name:..., mimeType:..., buffer:...}>
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
     * @param string|string[]|array{name: string, mimeType: string, buffer: string}|array<array{name: string, mimeType: string, buffer: string}> $files
     * @param array<string, mixed>                                                                                                               $options
     */
    public function setFiles(string|array $files, array $options = []): void
    {
        $payload = [
            'action' => 'fileChooser.setFiles',
            'elementId' => $this->elementId,
        ];

        if (is_string($files)) {
            $payload['files'] = [$files];
        } elseif (is_array($files)) {
            $payload['files'] = $files;
        }

        if (!empty($options)) {
            $payload['options'] = $options;
        }

        $this->transport->send($payload);
    }
}
