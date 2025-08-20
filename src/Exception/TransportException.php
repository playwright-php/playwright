<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Exception;

/**
 * Base exception for transport layer errors.
 * This includes process launching, communication, and connection issues.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class TransportException extends PlaywrightException
{
}
