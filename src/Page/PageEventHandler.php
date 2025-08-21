<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Page;

use PlaywrightPHP\Event\EventEmitter;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class PageEventHandler implements PageEventHandlerInterface
{
    use EventEmitter {
        emit as protected;
    }

    public function publicEmit(string $event, array $args = []): void
    {
        $this->emit($event, $args);
    }

    public function onDialog(callable $handler): void
    {
        $this->on('dialog', $handler);
    }

    public function onConsole(callable $handler): void
    {
        $this->on('console', $handler);
    }

    public function onRequest(callable $handler): void
    {
        $this->on('request', $handler);
    }

    public function onResponse(callable $handler): void
    {
        $this->on('response', $handler);
    }

    public function onRequestFailed(callable $handler): void
    {
        $this->on('requestfailed', $handler);
    }

    public function onRoute(callable $handler): void
    {
        $this->on('route', $handler);
    }
}
