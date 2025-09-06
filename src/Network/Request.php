<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Network;

use PlaywrightPHP\Exception\ProtocolErrorException;

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
            throw new ProtocolErrorException('Invalid URL in request data', 0);
        }

        return $url;
    }

    public function method(): string
    {
        $method = $this->data['method'];
        if (!is_string($method)) {
            throw new ProtocolErrorException('Invalid method in request data', 0);
        }

        return $method;
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

        $result = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                return null;
            }
            $result[$key] = $value;
        }

        return $result;
    }

    public function resourceType(): string
    {
        $resourceType = $this->data['resourceType'];
        if (!is_string($resourceType)) {
            throw new ProtocolErrorException('Invalid resourceType in request data', 0);
        }

        return $resourceType;
    }
}
