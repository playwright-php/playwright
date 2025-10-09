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
use Playwright\Frame\FrameInterface;
use Playwright\Transport\TransportInterface;

final class Request implements RequestInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data,
        private readonly ?TransportInterface $transport = null,
        private readonly ?string $requestId = null,
    ) {
    }

    public function url(): string
    {
        $url = $this->data['url'] ?? null;
        if (!is_string($url)) {
            throw new ProtocolErrorException('Invalid URL in request data', 0);
        }

        return $url;
    }

    public function method(): string
    {
        $method = $this->data['method'] ?? null;
        if (!is_string($method)) {
            throw new ProtocolErrorException('Invalid method in request data', 0);
        }

        return $method;
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

        $stringHeaders = [];
        foreach ($headers as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $stringHeaders[$key] = (string) $value;
            }
        }

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

        if (!json_validate($postData)) {
            return null;
        }

        $decoded = json_decode($postData, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            return null;
        }

        $result = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function resourceType(): string
    {
        $resourceType = $this->data['resourceType'] ?? null;
        if (!is_string($resourceType)) {
            throw new ProtocolErrorException('Invalid resourceType in request data', 0);
        }

        return $resourceType;
    }

    /**
     * Alias of headers().
     *
     * @return array<string, string>
     */
    public function allHeaders(): array
    {
        if (null === $this->transport || null === $this->requestId) {
            return $this->headers();
        }

        $response = $this->transport->send([
            'action' => 'request.allHeaders',
            'requestId' => $this->requestId,
        ]);

        $typed = [];
        foreach ($response as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $typed[$key] = $value;
            }
        }

        return $typed;
    }

    public function headerValue(string $name): ?string
    {
        if (null !== $this->transport && null !== $this->requestId) {
            $response = $this->transport->send([
                'action' => 'request.headerValue',
                'requestId' => $this->requestId,
                'name' => $name,
            ]);

            if (isset($response['value']) && is_string($response['value'])) {
                return $response['value'];
            }
        }

        return $this->headerValueFromLocalData($name);
    }

    /**
     * Headers as a list of name/value pairs; splits comma-separated values when using local cache.
     *
     * @return array<array{name: string, value: string}>
     */
    public function headersArray(): array
    {
        if (null !== $this->transport && null !== $this->requestId) {
            $response = $this->transport->send([
                'action' => 'request.headersArray',
                'requestId' => $this->requestId,
            ]);

            $typed = [];
            foreach ($response as $header) {
                if (is_array($header)
                    && isset($header['name'], $header['value'])
                    && is_string($header['name'])
                    && is_string($header['value'])
                ) {
                    $typed[] = ['name' => $header['name'], 'value' => $header['value']];
                }
            }

            if (!empty($typed)) {
                return $typed;
            }
        }

        return $this->headersArrayFallback();
    }

    public function isNavigationRequest(): bool
    {
        return (bool) ($this->data['isNavigationRequest'] ?? false);
    }

    public function postDataBuffer(): ?string
    {
        $buffer = $this->data['postDataBuffer'] ?? null;

        return is_string($buffer) ? $buffer : null;
    }

    /**
     * @return array{errorText: string}|null
     */
    public function failure(): ?array
    {
        $failure = $this->data['failure'] ?? null;

        if (!is_array($failure)) {
            return null;
        }

        if (!isset($failure['errorText']) || !is_string($failure['errorText'])) {
            return null;
        }

        return ['errorText' => $failure['errorText']];
    }

    public function frame(): ?FrameInterface
    {
        return null;
    }

    public function redirectedFrom(): ?RequestInterface
    {
        $requestData = $this->data['redirectedFrom'] ?? null;

        if (!is_array($requestData)) {
            return null;
        }

        $typedData = [];
        foreach ($requestData as $key => $value) {
            if (is_string($key)) {
                $typedData[$key] = $value;
            }
        }

        $requestId = isset($requestData['requestId']) && is_string($requestData['requestId']) ? $requestData['requestId'] : null;

        return new self($typedData, $this->transport, $requestId);
    }

    public function redirectedTo(): ?RequestInterface
    {
        $requestData = $this->data['redirectedTo'] ?? null;

        if (!is_array($requestData)) {
            return null;
        }

        $typedData = [];
        foreach ($requestData as $key => $value) {
            if (is_string($key)) {
                $typedData[$key] = $value;
            }
        }

        $requestId = isset($requestData['requestId']) && is_string($requestData['requestId']) ? $requestData['requestId'] : null;

        return new self($typedData, $this->transport, $requestId);
    }

    public function response(): ?ResponseInterface
    {
        return null;
    }

    public function serviceWorker(): mixed
    {
        return $this->data['serviceWorker'] ?? null;
    }

    /**
     * @return array{requestBodySize: int, requestHeadersSize: int, responseBodySize: int, responseHeadersSize: int}
     */
    public function sizes(): array
    {
        if (null === $this->transport || null === $this->requestId) {
            return [
                'requestBodySize' => 0,
                'requestHeadersSize' => 0,
                'responseBodySize' => 0,
                'responseHeadersSize' => 0,
            ];
        }

        $result = $this->transport->send([
            'action' => 'request.sizes',
            'requestId' => $this->requestId,
        ]);

        $requestBodySize = $result['requestBodySize'] ?? 0;
        $requestHeadersSize = $result['requestHeadersSize'] ?? 0;
        $responseBodySize = $result['responseBodySize'] ?? 0;
        $responseHeadersSize = $result['responseHeadersSize'] ?? 0;

        return [
            'requestBodySize' => is_numeric($requestBodySize) ? (int) $requestBodySize : 0,
            'requestHeadersSize' => is_numeric($requestHeadersSize) ? (int) $requestHeadersSize : 0,
            'responseBodySize' => is_numeric($responseBodySize) ? (int) $responseBodySize : 0,
            'responseHeadersSize' => is_numeric($responseHeadersSize) ? (int) $responseHeadersSize : 0,
        ];
    }

    /**
     * @return array{startTime: float, domainLookupStart: float, domainLookupEnd: float, connectStart: float, secureConnectionStart: float, connectEnd: float, requestStart: float, responseStart: float, responseEnd: float}
     */
    public function timing(): array
    {
        $timing = $this->data['timing'] ?? [];

        if (!is_array($timing)) {
            return [
                'startTime' => -1.0,
                'domainLookupStart' => -1.0,
                'domainLookupEnd' => -1.0,
                'connectStart' => -1.0,
                'secureConnectionStart' => -1.0,
                'connectEnd' => -1.0,
                'requestStart' => -1.0,
                'responseStart' => -1.0,
                'responseEnd' => -1.0,
            ];
        }

        $startTime = $timing['startTime'] ?? -1;
        $domainLookupStart = $timing['domainLookupStart'] ?? -1;
        $domainLookupEnd = $timing['domainLookupEnd'] ?? -1;
        $connectStart = $timing['connectStart'] ?? -1;
        $secureConnectionStart = $timing['secureConnectionStart'] ?? -1;
        $connectEnd = $timing['connectEnd'] ?? -1;
        $requestStart = $timing['requestStart'] ?? -1;
        $responseStart = $timing['responseStart'] ?? -1;
        $responseEnd = $timing['responseEnd'] ?? -1;

        return [
            'startTime' => is_numeric($startTime) ? (float) $startTime : -1.0,
            'domainLookupStart' => is_numeric($domainLookupStart) ? (float) $domainLookupStart : -1.0,
            'domainLookupEnd' => is_numeric($domainLookupEnd) ? (float) $domainLookupEnd : -1.0,
            'connectStart' => is_numeric($connectStart) ? (float) $connectStart : -1.0,
            'secureConnectionStart' => is_numeric($secureConnectionStart) ? (float) $secureConnectionStart : -1.0,
            'connectEnd' => is_numeric($connectEnd) ? (float) $connectEnd : -1.0,
            'requestStart' => is_numeric($requestStart) ? (float) $requestStart : -1.0,
            'responseStart' => is_numeric($responseStart) ? (float) $responseStart : -1.0,
            'responseEnd' => is_numeric($responseEnd) ? (float) $responseEnd : -1.0,
        ];
    }

    private function headerValueFromLocalData(string $name): ?string
    {
        $headers = $this->headers();
        $lowerName = strtolower($name);

        foreach ($headers as $key => $value) {
            if (strtolower($key) !== $lowerName) {
                continue;
            }

            $parts = array_map('trim', explode(',', $value));
            foreach ($parts as $part) {
                if ('' !== $part) {
                    return $part;
                }
            }

            return '';
        }

        return null;
    }

    /**
     * @return array<array{name: string, value: string}>
     */
    private function headersArrayFallback(): array
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
}
