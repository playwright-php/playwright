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

namespace Playwright\Transport;

use Playwright\Exception\DisconnectedException;
use Playwright\Exception\PlaywrightException;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Exception\TimeoutException;

/**
 * Converts low-level protocol error payloads into typed exceptions.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ErrorMapper
{
    /**
     * @param array<string, mixed>      $error     Protocol error payload (e.g., { name, message, code, stack })
     * @param string|null               $method    Protocol method being executed
     * @param array<string, mixed>|null $params    Parameters for the method
     * @param float|null                $timeoutMs Timeout used for this request, if any
     */
    public static function toException(
        array $error,
        ?string $method,
        ?array $params,
        ?float $timeoutMs,
    ): PlaywrightException {
        $name = self::getString($error, 'name');
        $message = self::getString($error, 'message') ?? 'Protocol error';
        $code = self::getInt($error, 'code') ?? 0;
        $stack = self::getString($error, 'stack') ?? self::getString($error, 'remoteStack');

        if ('TimeoutError' === $name || (408 === $code /* HTTP timeout-like */)) {
            return new TimeoutException(
                $message,
                $timeoutMs ?? 0.0,
                null,
                [
                    'method' => $method,
                    'params' => Sanitizer::sanitizeParams($params),
                    'protocolName' => $name,
                    'protocolCode' => $code,
                    'remoteStack' => $stack,
                ]
            );
        }

        if ('TargetClosedError' === $name || 'DisconnectedError' === $name) {
            return new DisconnectedException(
                $message,
                $code,
                null,
                [
                    'method' => $method,
                    'params' => Sanitizer::sanitizeParams($params),
                    'protocolName' => $name,
                    'protocolCode' => $code,
                    'remoteStack' => $stack,
                ]
            );
        }

        return new ProtocolErrorException(
            $message,
            $code,
            $name,
            $method,
            Sanitizer::sanitizeParams($params),
            $stack
        );
    }

    /**
     * @param array<string, mixed> $arr
     */
    private static function getString(array $arr, string $key): ?string
    {
        $v = $arr[$key] ?? null;

        return is_string($v) ? $v : null;
    }

    /**
     * @param array<string, mixed> $arr
     */
    private static function getInt(array $arr, string $key): ?int
    {
        $v = $arr[$key] ?? null;

        return is_int($v) ? $v : (is_numeric($v) ? (int) $v : null);
    }
}
