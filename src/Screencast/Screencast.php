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

namespace Playwright\Screencast;

use Playwright\Exception\PlaywrightException;
use Playwright\Transport\TransportInterface;

final class Screencast implements ScreencastInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $pageId,
    ) {
    }

    public function hideActions(): void
    {
        $this->send([
            'action' => 'screencast.hideActions',
            'pageId' => $this->pageId,
        ]);
    }

    public function hideOverlays(): void
    {
        $this->send([
            'action' => 'screencast.hideOverlays',
            'pageId' => $this->pageId,
        ]);
    }

    public function showActions(array $options = []): void
    {
        $this->send([
            'action' => 'screencast.showActions',
            'pageId' => $this->pageId,
            'options' => $options,
        ]);
    }

    public function showChapter(string $title, array $options = []): void
    {
        $this->send([
            'action' => 'screencast.showChapter',
            'pageId' => $this->pageId,
            'title' => $title,
            'options' => $options,
        ]);
    }

    public function showOverlay(string $html, array $options = []): void
    {
        $this->send([
            'action' => 'screencast.showOverlay',
            'pageId' => $this->pageId,
            'html' => $html,
            'options' => $options,
        ]);
    }

    public function showOverlays(): void
    {
        $this->send([
            'action' => 'screencast.showOverlays',
            'pageId' => $this->pageId,
        ]);
    }

    public function start(array $options = []): void
    {
        $this->send([
            'action' => 'screencast.start',
            'pageId' => $this->pageId,
            'options' => $options,
        ]);
    }

    public function stop(): void
    {
        $this->send([
            'action' => 'screencast.stop',
            'pageId' => $this->pageId,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function send(array $payload): array
    {
        $response = $this->transport->send($payload);

        if (isset($response['error'])) {
            $error = $response['error'];

            throw new PlaywrightException(is_string($error) ? $error : 'Unknown Playwright server error');
        }

        return $response;
    }
}
