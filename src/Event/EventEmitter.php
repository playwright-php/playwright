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

namespace Playwright\Event;

trait EventEmitter
{
    /** @var array<string, array<callable>> */
    private array $listeners = [];

    public function on(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $listener;
    }

    public function once(string $event, callable $listener): void
    {
        $wrapper = null;
        $wrapper = function (...$args) use ($event, $listener, &$wrapper) {
            $this->removeListener($event, $wrapper);
            $listener(...$args);
        };
        $this->on($event, $wrapper);
    }

    public function removeListener(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }
        $index = array_search($listener, $this->listeners[$event], true);
        if (false !== $index) {
            array_splice($this->listeners[$event], (int) $index, 1);
        }
    }

    /**
     * @param array<mixed> $args
     */
    protected function emit(string $event, array $args = []): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }
        foreach ($this->listeners[$event] as $listener) {
            $listener(...$args);
        }
    }
}
