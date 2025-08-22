<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Network;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class Request implements RequestInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data,
    ) {
    }

    public function url(): string
    {
        $url = $this->data['url'];
        if (!is_string($url)) {
            throw new \RuntimeException('Invalid URL in request data');
        }

        return $url;
    }

    public function method(): string
    {
        $method = $this->data['method'];
        if (!is_string($method)) {
            throw new \RuntimeException('Invalid method in request data');
        }

        return $method;
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

    public function postData(): ?string
    {
        $postData = $this->data['postData'] ?? null;

        return is_string($postData) ? $postData : null;
    }

    public function postDataJSON(): ?array
    {
        $postData = $this->postData();
        if (null === $postData) {
            return null;
        }

        $decoded = json_decode($postData, true);

        if (!is_array($decoded)) {
            return null;
        }

        // PHPStan hint: after validation, this is array<string, mixed>
        /* @phpstan-var array<string, mixed> $decoded */
        return $decoded;
    }

    public function resourceType(): string
    {
        $resourceType = $this->data['resourceType'];
        if (!is_string($resourceType)) {
            throw new \RuntimeException('Invalid resourceType in request data');
        }

        return $resourceType;
    }
}
