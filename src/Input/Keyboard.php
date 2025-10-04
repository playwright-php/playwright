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

namespace Playwright\Input;

use Playwright\Transport\TransportInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class Keyboard implements KeyboardInterface
{
    private string $pageId;
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport, string $pageId)
    {
        $this->transport = $transport;
        $this->pageId = $pageId;
    }

    public function down(string $key): void
    {
        $this->transport->send([
            'action' => 'keyboard.down',
            'pageId' => $this->pageId,
            'key' => $key,
        ]);

        $this->transport->processEvents();
    }

    public function insertText(string $text): void
    {
        $this->transport->send([
            'action' => 'keyboard.insertText',
            'pageId' => $this->pageId,
            'text' => $text,
        ]);
    }

    public function press(string $key, array $options = []): void
    {
        $this->transport->send([
            'action' => 'keyboard.press',
            'pageId' => $this->pageId,
            'key' => $key,
            'options' => $options,
        ]);

        $this->transport->processEvents();
    }

    public function type(string $text, array $options = []): void
    {
        $this->transport->send([
            'action' => 'keyboard.type',
            'pageId' => $this->pageId,
            'text' => $text,
            'options' => $options,
        ]);
    }

    public function up(string $key): void
    {
        $this->transport->send([
            'action' => 'keyboard.up',
            'pageId' => $this->pageId,
            'key' => $key,
        ]);

        $this->transport->processEvents();
    }
}
