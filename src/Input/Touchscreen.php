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

final class Touchscreen implements TouchscreenInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $pageId,
    ) {
    }

    public function tap(float $x, float $y): void
    {
        $this->transport->send([
            'action' => 'touchscreen.tap',
            'pageId' => $this->pageId,
            'x' => $x,
            'y' => $y,
        ]);

        $this->transport->processEvents();
    }
}
