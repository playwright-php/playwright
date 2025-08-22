<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Event;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface EventDispatcherInterface
{
    /**
     * @param array<string, mixed> $params
     */
    public function dispatchEvent(string $eventName, array $params): void;
}
