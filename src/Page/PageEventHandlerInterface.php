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

namespace Playwright\Page;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface PageEventHandlerInterface
{
    /**
     * @param array<mixed> $args
     */
    public function publicEmit(string $event, array $args = []): void;

    public function onDialog(callable $handler): void;

    public function onConsole(callable $handler): void;

    public function onRequest(callable $handler): void;

    public function onResponse(callable $handler): void;

    public function onRequestFailed(callable $handler): void;

    public function onRoute(callable $handler): void;
}
