<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Exception;

/**
 * Thrown when the browser/context connection is lost unexpectedly.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class DisconnectedException extends PlaywrightException
{
}
