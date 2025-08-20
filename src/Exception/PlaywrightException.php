<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Exception;

/**
 * Base exception for all Playwright PHP errors.
 * Catch this to handle any library-specific error.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class PlaywrightException extends \RuntimeException implements PlaywrightExceptionInterface
{
    /** @var array<string, mixed> */
    private array $context;

    /**
     * @param array<string, mixed> $context Structured data to aid debugging (e.g., method, selector, url).
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
