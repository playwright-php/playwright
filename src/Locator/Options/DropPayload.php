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

namespace Playwright\Locator\Options;

/**
 * What a drop carries: files and/or clipboard-like entries keyed by mime type.
 */
final readonly class DropPayload
{
    /**
     * A file entry is either a path on disk or an in-memory file whose buffer holds
     * raw bytes. Data entries are keyed by mime type, such as text/plain.
     *
     * @param list<string|array{name: string, mimeType: string, buffer: string}> $files
     * @param array<string, string>                                              $data
     */
    public function __construct(
        public array $files = [],
        public array $data = [],
    ) {
    }

    /**
     * Buffers travel base64 encoded because the wire format is JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [];
        if ([] !== $this->files) {
            $payload['files'] = array_map(
                /**
                 * @param string|array{name: string, mimeType: string, buffer: string} $file
                 *
                 * @return string|array{name: string, mimeType: string, buffer: string}
                 */
                static fn (string|array $file): string|array => is_string($file) ? $file : [
                    'name' => $file['name'],
                    'mimeType' => $file['mimeType'],
                    'buffer' => base64_encode($file['buffer']),
                ],
                $this->files,
            );
        }
        if ([] !== $this->data) {
            $payload['data'] = $this->data;
        }

        return $payload;
    }

    /**
     * @param array<string, mixed>|self $payload
     */
    public static function from(array|self $payload = []): self
    {
        if ($payload instanceof self) {
            return $payload;
        }

        /** @var string|list<string|array{name: string, mimeType: string, buffer: string}> $files */
        $files = $payload['files'] ?? [];
        /** @var array<string, string> $data */
        $data = $payload['data'] ?? [];

        return new self(is_string($files) ? [$files] : $files, $data);
    }
}
