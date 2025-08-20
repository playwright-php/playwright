<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Network;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface RouteInterface
{
    public function request(): RequestInterface;

    public function abort(string $errorCode = 'failed'): void;

    public function continue(?array $options = null): void;

    public function fulfill(array $options): void;
}
