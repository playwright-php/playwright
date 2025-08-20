<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Transport\JsonRpc;

/**
 * LSP-style message framing for reliable communication.
 *
 * @experimental
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class LspFraming
{
    public static function encode(string $content): string
    {
        $contentLength = strlen($content);

        return sprintf("Content-Length: %d\r\n\r\n%s", $contentLength, $content);
    }

    /**
     * @return array{messages: list<string>, remainingBuffer: string}
     */
    public static function decode(string $buffer): array
    {
        $messages = [];
        $remaining = $buffer;
        while (true) {
            $result = self::extractOneMessage($remaining);
            if (null === $result) {
                break;
            }
            [$message, $remaining] = $result;
            $messages[] = $message;
        }

        return ['messages' => $messages, 'remainingBuffer' => $remaining];
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function extractOneMessage(string $buffer): ?array
    {
        $headerEndPos = strpos($buffer, "\r\n\r\n");
        if (false === $headerEndPos) {
            return null;
        }

        $headers = substr($buffer, 0, $headerEndPos);
        $contentStart = $headerEndPos + 4;

        $contentLength = self::parseContentLength($headers);
        if (null === $contentLength) {
            throw new \InvalidArgumentException('Missing or invalid Content-Length header');
        }

        if (strlen($buffer) < $contentStart + $contentLength) {
            return null;
        }

        $content = substr($buffer, $contentStart, $contentLength);
        $remaining = substr($buffer, $contentStart + $contentLength);

        return [$content, $remaining];
    }

    private static function parseContentLength(string $headers): ?int
    {
        $lines = explode("\r\n", $headers);
        foreach ($lines as $line) {
            if (preg_match('/^Content-Length:\s*(\d+)$/i', trim($line), $matches)) {
                return (int) $matches[1];
            }
        }

        return null;
    }

    public static function hasCompleteMessage(string $buffer): bool
    {
        return null !== self::extractOneMessage($buffer);
    }

    public static function getExpectedLength(string $buffer): ?int
    {
        $headerEndPos = strpos($buffer, "\r\n\r\n");
        if (false === $headerEndPos) {
            return null;
        }
        $headers = substr($buffer, 0, $headerEndPos);
        $contentLength = self::parseContentLength($headers);
        if (null === $contentLength) {
            return null;
        }

        return $headerEndPos + 4 + $contentLength;
    }
}
