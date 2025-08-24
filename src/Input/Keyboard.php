<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Input;

use PlaywrightPHP\Transport\TransportInterface;

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
}
