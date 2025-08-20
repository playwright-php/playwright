<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Exception;

/**
 * Thrown when the Node sidecar crashes or exits unexpectedly.
 * Includes exit code and a stderr excerpt for diagnostics.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class ProcessCrashedException extends TransportException
{
    public function __construct(
        string $message,
        private readonly int $exitCode,
        private readonly string $stderrExcerpt,
        array $context = [],
        ?\Throwable $previous = null,
    ) {
        $context += [
            'exitCode' => $this->exitCode,
            'stderrExcerpt' => $this->stderrExcerpt,
        ];
        parent::__construct($message, 0, $previous, $context);
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getStderrExcerpt(): string
    {
        return $this->stderrExcerpt;
    }
}
