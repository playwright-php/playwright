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

final class Mouse implements MouseInterface
{
    private string $pageId;
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport, string $pageId)
    {
        $this->transport = $transport;
        $this->pageId = $pageId;
    }

    public function click(float $x, float $y, array $options = []): void
    {
        $this->transport->send([
            'action' => 'mouse.click',
            'pageId' => $this->pageId,
            'x' => $x,
            'y' => $y,
            'options' => $options,
        ]);

        $this->transport->processEvents();
    }

    public function move(float $x, float $y, array $options = []): void
    {
        $this->transport->send([
            'action' => 'mouse.move',
            'pageId' => $this->pageId,
            'x' => $x,
            'y' => $y,
            'options' => $options,
        ]);
    }

    public function wheel(float $deltaX, float $deltaY): void
    {
        $this->transport->send([
            'action' => 'mouse.wheel',
            'pageId' => $this->pageId,
            'deltaX' => $deltaX,
            'deltaY' => $deltaY,
        ]);
    }
}
