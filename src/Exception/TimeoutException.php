<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Exception;

/**
 * Thrown when an operation times out.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class TimeoutException extends PlaywrightException
{
    public function __construct(
        string $message,
        private readonly float $timeoutMs = 0.0,
        ?\Throwable $previous = null,
        array $context = [],
    ) {
        $context['timeoutMs'] = $this->timeoutMs;
        parent::__construct($message, 0, $previous, $context);
    }

    public function getTimeoutMs(): float
    {
        return $this->timeoutMs;
    }
}
