<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Node\Exception;

use PlaywrightPHP\Exception\PlaywrightException;

/**
 * Thrown when Node.js is found but version is too low.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class NodeVersionTooLowException extends PlaywrightException
{
}
