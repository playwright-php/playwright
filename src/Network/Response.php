<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Network;

use PlaywrightPHP\Transport\TransportInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
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
            throw new \RuntimeException('Invalid URL in response data');
        }

        return $url;
    }

    public function status(): int
    {
        $status = $this->data['status'];
        if (!is_int($status)) {
            throw new \RuntimeException('Invalid status in response data');
        }

        return $status;
    }

    public function statusText(): string
    {
        $statusText = $this->data['statusText'];
        if (!is_string($statusText)) {
            throw new \RuntimeException('Invalid statusText in response data');
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

        // Convert to proper string-to-string mapping
        $stringHeaders = [];
        foreach ($headers as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $stringHeaders[$key] = (string) $value;
            }
        }

        /* @phpstan-var array<string, string> $stringHeaders */
        return $stringHeaders;
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
                throw new \RuntimeException('Invalid binary response data');
            }
            $decoded = base64_decode($binary);
            if (false === $decoded) {
                throw new \RuntimeException('Failed to decode binary response data');
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
            // Ensure we always have an array with proper typing
            if (is_array($decoded)) {
                /* @phpstan-var array<string, mixed> $decoded */
                $this->jsonCache = $decoded;
            } else {
                $this->jsonCache = [];
            }
        }

        return $this->jsonCache;
    }
}
