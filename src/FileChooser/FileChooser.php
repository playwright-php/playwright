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
        return (bool) ($this->data['isMultiple'] ?? false);
    }

    public function page(): PageInterface
    {
        return $this->page;
    }

    /**
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
