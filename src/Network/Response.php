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
class Response implements ResponseInterface
{
    private ?string $body = null;
    private ?array $jsonCache = null;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $pageId,
        private readonly array $data,
    ) {
    }

    public function url(): string
    {
        return $this->data['url'];
    }

    public function status(): int
    {
        return $this->data['status'];
    }

    public function statusText(): string
    {
        return $this->data['statusText'];
    }

    public function ok(): bool
    {
        return $this->status() >= 200 && $this->status() <= 299;
    }

    public function headers(): array
    {
        return $this->data['headers'];
    }

    public function body(): string
    {
        if (null === $this->body) {
            $response = $this->transport->send([
                'action' => 'response.body',
                'pageId' => $this->pageId,
                'responseId' => $this->data['responseId'],
            ]);
            $this->body = base64_decode($response['binary']);
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
            $this->jsonCache = $decoded;
        }

        return $this->jsonCache;
    }
}
