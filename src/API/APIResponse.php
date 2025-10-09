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

namespace Playwright\API;

use Playwright\Exception\PlaywrightException;

final class APIResponse implements APIResponseInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data,
    ) {
    }

    public function ok(): bool
    {
        $status = $this->status();

        return $status >= 200 && $status < 300;
    }

    public function status(): int
    {
        $status = $this->data['status'] ?? 0;

        return is_int($status) ? $status : 0;
    }

    public function statusText(): string
    {
        $statusText = $this->data['statusText'] ?? '';

        return is_string($statusText) ? $statusText : '';
    }

    public function url(): string
    {
        $url = $this->data['url'] ?? '';

        return is_string($url) ? $url : '';
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        $headers = $this->data['headers'] ?? [];

        if (!is_array($headers)) {
            return [];
        }

        $result = [];
        foreach ($headers as $name => $value) {
            if (is_string($name)) {
                if (is_string($value)) {
                    $result[$name] = $value;
                } elseif (is_array($value) && isset($value[0]) && is_string($value[0])) {
                    $result[$name] = $value[0];
                }
            }
        }

        return $result;
    }

    /**
     * @return array<string, string[]>
     */
    public function headersArray(): array
    {
        $headers = $this->data['headers'] ?? [];

        if (!is_array($headers)) {
            return [];
        }

        $result = [];
        foreach ($headers as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            if (is_string($value)) {
                $result[$name] = [$value];
            } elseif (is_array($value)) {
                $stringValues = array_filter($value, 'is_string');
                if (!empty($stringValues)) {
                    $result[$name] = array_values($stringValues);
                }
            }
        }

        return $result;
    }

    public function headerValue(string $name): ?string
    {
        $headers = $this->headers();
        $lowerName = strtolower($name);

        foreach ($headers as $headerName => $value) {
            if (strtolower($headerName) === $lowerName) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<string, string[]>
     */
    public function headerValues(string $name): array
    {
        $headers = $this->headersArray();
        $lowerName = strtolower($name);

        foreach ($headers as $headerName => $values) {
            if (strtolower($headerName) === $lowerName) {
                return [$headerName => $values];
            }
        }

        return [];
    }

    public function body(): string
    {
        return $this->text();
    }

    /**
     * @return array<string, mixed>
     */
    public function json(): array
    {
        $body = $this->data['body'] ?? '';

        if (!is_string($body)) {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, \JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                return [];
            }

            $result = [];
            foreach ($decoded as $key => $value) {
                if (is_string($key)) {
                    $result[$key] = $value;
                    continue;
                }

                $result[(string) $key] = $value;
            }

            return $result;
        } catch (\JsonException $e) {
            throw new PlaywrightException('Response body is not valid JSON: '.$e->getMessage(), 0, $e);
        }
    }

    public function text(): string
    {
        $body = $this->data['body'] ?? '';

        return is_string($body) ? $body : '';
    }

    public function dispose(): void
    {
    }
}
