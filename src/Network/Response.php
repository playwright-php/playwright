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

namespace Playwright\Network;

use Playwright\Exception\ProtocolErrorException;
use Playwright\Transport\TransportInterface;

final class Response implements ResponseInterface
{
    private ?string $body = null;
    /** @var array<string, mixed>|null */
    private ?array $jsonCache = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $pageId,
        private readonly array $data,
    ) {
    }

    public function url(): string
    {
        $url = $this->data['url'];
        if (!is_string($url)) {
            throw new ProtocolErrorException('Invalid URL in response data', 0);
        }

        return $url;
    }

    public function status(): int
    {
        $status = $this->data['status'];
        if (!is_int($status)) {
            throw new ProtocolErrorException('Invalid status in response data', 0);
        }

        return $status;
    }

    public function statusText(): string
    {
        $statusText = $this->data['statusText'];
        if (!is_string($statusText)) {
            throw new ProtocolErrorException('Invalid statusText in response data', 0);
        }

        return $statusText;
    }

    public function ok(): bool
    {
        return $this->status() >= 200 && $this->status() <= 299;
    }

    public function headers(): array
    {
        $headers = $this->data['headers'];
        if (!is_array($headers)) {
            return [];
        }

        $stringHeaders = [];
        foreach ($headers as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $stringHeaders[$key] = (string) $value;
            }
        }

        /* @var array<string, string> $stringHeaders */
        return $stringHeaders;
    }

    /**
     * Case-insensitive single header value (first value if multiple), or null if absent.
     */
    public function headerValue(string $name): ?string
    {
        $lower = strtolower($name);
        foreach ($this->headers() as $k => $v) {
            if (strtolower($k) === $lower) {
                // If multiple (comma-separated), return first trimmed part
                $parts = array_map('trim', explode(',', $v));
                foreach ($parts as $part) {
                    if ('' !== $part) {
                        return $part;
                    }
                }

                return '';
            }
        }

        return null;
    }

    /**
     * Case-insensitive multiple header values (split on commas).
     *
     * @return array<string>
     */
    public function headerValues(string $name): array
    {
        $lower = strtolower($name);
        foreach ($this->headers() as $k => $v) {
            if (strtolower($k) === $lower) {
                $parts = array_map('trim', explode(',', $v));

                return array_values(array_filter($parts, fn ($p) => '' !== $p));
            }
        }

        return [];
    }

    /**
     * Headers as a list of name/value pairs; values split on commas.
     *
     * @return array<array{name: string, value: string}>
     */
    public function headersArray(): array
    {
        $result = [];
        foreach ($this->headers() as $name => $value) {
            $parts = array_map('trim', explode(',', $value));
            foreach ($parts as $part) {
                if ('' === $part) {
                    continue;
                }
                $result[] = ['name' => $name, 'value' => $part];
            }
        }

        return $result;
    }

    public function body(): string
    {
        if (null === $this->body) {
            $response = $this->transport->send([
                'action' => 'response.body',
                'pageId' => $this->pageId,
                'responseId' => $this->data['responseId'],
            ]);
            $binary = $response['binary'];
            if (!is_string($binary)) {
                throw new ProtocolErrorException('Invalid binary response data', 0);
            }
            $decoded = base64_decode($binary);
            if (false === $decoded) {
                throw new ProtocolErrorException('Failed to decode binary response data', 0);
            }
            $this->body = $decoded;
        }

        return $this->body;
    }

    public function text(): string
    {
        return $this->body();
    }

    public function json(): array
    {
        if (null === $this->jsonCache) {
            $decoded = json_decode($this->text(), true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \JsonException('Invalid JSON: '.json_last_error_msg());
            }

            if (is_array($decoded)) {
                $result = [];
                foreach ($decoded as $key => $value) {
                    if (!is_string($key)) {
                        $result = [];
                        break;
                    }
                    $result[$key] = $value;
                }
                $this->jsonCache = $result;
            } else {
                $this->jsonCache = [];
            }
        }

        return $this->jsonCache;
    }

    /**
     * @return array<string, string>
     */
    public function allHeaders(): array
    {
        $response = $this->transport->send([
            'action' => 'response.allHeaders',
            'pageId' => $this->pageId,
            'responseId' => $this->data['responseId'],
        ]);

        $typed = [];
        foreach ($response as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $typed[$key] = $value;
            }
        }

        return $typed;
    }

    public function finished(): ?string
    {
        $response = $this->transport->send([
            'action' => 'response.finished',
            'pageId' => $this->pageId,
            'responseId' => $this->data['responseId'],
        ]);

        if (isset($response['error']) && is_string($response['error'])) {
            return $response['error'];
        }

        return null;
    }

    public function frame(): ?\Playwright\Frame\FrameInterface
    {
        // TODO: Frame constructor requires pageId and frameSelector - need frameId from response data
        return null;
    }

    public function fromServiceWorker(): bool
    {
        return (bool) ($this->data['fromServiceWorker'] ?? false);
    }

    public function headerValue(string $name): ?string
    {
        $response = $this->transport->send([
            'action' => 'response.headerValue',
            'pageId' => $this->pageId,
            'responseId' => $this->data['responseId'],
            'name' => $name,
        ]);

        if (isset($response['value']) && is_string($response['value'])) {
            return $response['value'];
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function headerValues(string $name): array
    {
        $response = $this->transport->send([
            'action' => 'response.headerValues',
            'pageId' => $this->pageId,
            'responseId' => $this->data['responseId'],
            'name' => $name,
        ]);

        if (isset($response['values']) && is_array($response['values'])) {
            $typed = [];
            foreach ($response['values'] as $value) {
                if (is_string($value)) {
                    $typed[] = $value;
                }
            }

            return $typed;
        }

        return [];
    }

    /**
     * @return array{name: string, value: string}[]
     */
    public function headersArray(): array
    {
        $response = $this->transport->send([
            'action' => 'response.headersArray',
            'pageId' => $this->pageId,
            'responseId' => $this->data['responseId'],
        ]);

        $typed = [];
        foreach ($response as $header) {
            if (is_array($header) && isset($header['name'], $header['value']) && is_string($header['name']) && is_string($header['value'])) {
                $typed[] = ['name' => $header['name'], 'value' => $header['value']];
            }
        }

        return $typed;
    }

    public function request(): RequestInterface
    {
        $requestData = $this->data['request'] ?? [];

        if (!is_array($requestData)) {
            $requestData = [];
        }

        $typedData = [];
        foreach ($requestData as $key => $value) {
            if (is_string($key)) {
                $typedData[$key] = $value;
            }
        }

        $requestId = isset($requestData['requestId']) && is_string($requestData['requestId']) ? $requestData['requestId'] : null;

        return new Request($typedData, $this->transport, $requestId);
    }

    /**
     * @return array{issuer?: string, protocol?: string, subjectName?: string, validFrom?: int, validTo?: int}|null
     */
    public function securityDetails(): ?array
    {
        $response = $this->transport->send([
            'action' => 'response.securityDetails',
            'pageId' => $this->pageId,
            'responseId' => $this->data['responseId'],
        ]);

        if (empty($response)) {
            return null;
        }

        $result = [];

        if (isset($response['issuer']) && is_string($response['issuer'])) {
            $result['issuer'] = $response['issuer'];
        }
        if (isset($response['protocol']) && is_string($response['protocol'])) {
            $result['protocol'] = $response['protocol'];
        }
        if (isset($response['subjectName']) && is_string($response['subjectName'])) {
            $result['subjectName'] = $response['subjectName'];
        }
        if (isset($response['validFrom']) && is_numeric($response['validFrom'])) {
            $result['validFrom'] = (int) $response['validFrom'];
        }
        if (isset($response['validTo']) && is_numeric($response['validTo'])) {
            $result['validTo'] = (int) $response['validTo'];
        }

        return empty($result) ? null : $result;
    }

    /**
     * @return array{ipAddress: string, port: int}|null
     */
    public function serverAddr(): ?array
    {
        $response = $this->transport->send([
            'action' => 'response.serverAddr',
            'pageId' => $this->pageId,
            'responseId' => $this->data['responseId'],
        ]);

        if (!isset($response['ipAddress'], $response['port'])) {
            return null;
        }

        if (!is_string($response['ipAddress']) || !is_numeric($response['port'])) {
            return null;
        }

        return [
            'ipAddress' => $response['ipAddress'],
            'port' => (int) $response['port'],
        ];
    }
}
