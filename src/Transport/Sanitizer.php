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

use Playwright\Exception\RuntimeException;

/**
 * Utility class for sanitizing sensitive data from logs and error messages.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class Sanitizer
{
    /**
     * @var string[]
     */
    private const SENSITIVE_KEYS = [
        'password',
        'passwd',
        'secret',
        'token',
        'auth',
        'authorization',
        'cookie',
        'session',
        'key',
        'private',
        'credential',
        'api_key',
        'access_token',
        'refresh_token',
    ];

    private const REPLACEMENT = '[REDACTED]';

    /**
     * Sanitize parameters by removing or masking sensitive values.
     *
     * @param mixed $params Parameters to sanitize
     *
     * @return mixed Sanitized parameters
     */
    public static function sanitizeParams(mixed $params): mixed
    {
        if (null === $params) {
            return null;
        }

        if (is_array($params)) {
            return self::sanitizeArray($params);
        }

        if (is_object($params)) {
            return self::sanitizeObject($params);
        }

        if (is_string($params)) {
            return self::sanitizeString($params);
        }

        return $params;
    }

    /**
     * Sanitize an array by recursively cleaning sensitive keys.
     *
     * @param array<mixed, mixed> $array
     *
     * @return array<mixed, mixed>
     */
    private static function sanitizeArray(array $array): array
    {
        $sanitized = [];

        foreach ($array as $key => $value) {
            if (is_string($key) && self::isSensitiveKey($key)) {
                $sanitized[$key] = self::REPLACEMENT;
            } else {
                $sanitized[$key] = self::sanitizeParams($value);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize an object by converting to array and sanitizing.
     */
    private static function sanitizeObject(object $object): object
    {
        $json = json_encode($object);
        if (false === $json) {
            throw new RuntimeException('Failed to encode object to JSON');
        }
        $array = json_decode($json, true);
        if (!is_array($array)) {
            throw new RuntimeException('Object could not be converted to array');
        }
        $sanitized = self::sanitizeArray($array);

        return (object) $sanitized;
    }

    /**
     * Sanitize a string by masking common patterns.
     */
    private static function sanitizeString(string $string): string
    {
        $patterns = [
            '/\b[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\b/' => '[JWT_TOKEN]',

            '/\b[a-z0-9]{32,}\b/i' => '[API_KEY]',

            '/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/i' => '[UUID]',

            '/:([^:@]+)@/' => ':[REDACTED]@',

            '/Basic [A-Za-z0-9+\/]+=*/' => 'Basic [REDACTED]',

            '/Bearer [A-Za-z0-9_\-\.]+/' => 'Bearer [REDACTED]',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $string);
            if (null === $result) {
                throw new RuntimeException('preg_replace failed');
            }
            $string = $result;
        }

        return $string;
    }

    /**
     * Check if a key name suggests it contains sensitive data.
     */
    private static function isSensitiveKey(string $key): bool
    {
        $lowerKey = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($lowerKey, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize a URL by removing sensitive information.
     */
    public static function sanitizeUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (false === $parsed) {
            return $url;
        }

        if (isset($parsed['user']) || isset($parsed['pass'])) {
            $parsed['user'] = '[REDACTED]';
            unset($parsed['pass']);
        }

        $scheme = isset($parsed['scheme']) ? $parsed['scheme'].'://' : '';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $user = isset($parsed['user']) ? $parsed['user'].'@' : '';
        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        return $scheme.$user.$host.$port.$path.$query.$fragment;
    }
}
