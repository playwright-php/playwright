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
}
