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

use Playwright\Exception\ProtocolErrorException;
use Playwright\Transport\TransportInterface;

final class APIRequestContext implements APIRequestContextInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $contextId,
        private readonly ?string $baseURL = null,
        private readonly bool $shareCookies = false,
    ) {
    }

    /**
     * Exposes whether this context shares cookies (derived from provided storage state).
     */
    public function getShareCookies(): bool
    {
        return $this->shareCookies;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function get(string $url, array $options = []): APIResponseInterface
    {
        return $this->fetch($url, [...$options, 'method' => 'GET']);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $url, array $options = []): APIResponseInterface
    {
        return $this->fetch($url, [...$options, 'method' => 'POST']);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function put(string $url, array $options = []): APIResponseInterface
    {
        return $this->fetch($url, [...$options, 'method' => 'PUT']);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function patch(string $url, array $options = []): APIResponseInterface
    {
        return $this->fetch($url, [...$options, 'method' => 'PATCH']);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function delete(string $url, array $options = []): APIResponseInterface
    {
        return $this->fetch($url, [...$options, 'method' => 'DELETE']);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function head(string $url, array $options = []): APIResponseInterface
    {
        return $this->fetch($url, [...$options, 'method' => 'HEAD']);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function fetch(string $urlOrRequest, array $options = []): APIResponseInterface
    {
        $url = $this->resolveURL($urlOrRequest);

        $response = $this->transport->send([
            'action' => 'api.fetch',
            'contextId' => $this->contextId,
            'url' => $url,
            'options' => $this->prepareOptions($options),
        ]);

        if (isset($response['error'])) {
            $error = $response['error'];
            $message = 'Unknown API request error';
            if (is_string($error)) {
                $message = $error;
            } elseif (is_array($error) && isset($error['message']) && is_string($error['message'])) {
                $message = $error['message'];
            }
            throw new ProtocolErrorException($message, 0);
        }

        if (!isset($response['response']) || !is_array($response['response'])) {
            throw new ProtocolErrorException('Invalid API response from transport', 0);
        }

        // Ensure we pass an array<string, mixed> to APIResponse
        $respData = [];
        foreach ($response['response'] as $key => $value) {
            if (is_string($key)) {
                $respData[$key] = $value;
            }
        }

        return new APIResponse($respData);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function storageState(?string $path = null): array
    {
        $response = $this->transport->send([
            'action' => 'api.storageState',
            'contextId' => $this->contextId,
            'path' => $path,
        ]);

        if (!isset($response['storageState']) || !is_array($response['storageState'])) {
            return [];
        }

        $result = [];
        foreach ($response['storageState'] as $item) {
            if (is_array($item)) {
                $validated = [];
                foreach ($item as $key => $value) {
                    if (is_string($key)) {
                        $validated[$key] = $value;
                    }
                }
                if (!empty($validated)) {
                    $result[] = $validated;
                }
            }
        }

        return $result;
    }

    public function dispose(): void
    {
        $this->transport->send([
            'action' => 'api.dispose',
            'contextId' => $this->contextId,
        ]);
    }

    private function resolveURL(string $url): string
    {
        if (null === $this->baseURL) {
            return $url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim($this->baseURL, '/').'/'.ltrim($url, '/');
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function prepareOptions(array $options): array
    {
        $prepared = $options;

        if (isset($options['data'])) {
            $data = $options['data'];
            if (is_array($data)) {
                $prepared['data'] = json_encode($data);
                if (!isset($prepared['headers'])) {
                    $prepared['headers'] = [];
                }
                if (is_array($prepared['headers']) && !isset($prepared['headers']['Content-Type'])) {
                    $prepared['headers']['Content-Type'] = 'application/json';
                }
            }
        }

        if (isset($options['form'])) {
            $form = $options['form'];
            if (is_array($form)) {
                $prepared['data'] = http_build_query($form);
                if (!isset($prepared['headers'])) {
                    $prepared['headers'] = [];
                }
                if (is_array($prepared['headers']) && !isset($prepared['headers']['Content-Type'])) {
                    $prepared['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
                }
            }
            unset($prepared['form']);
        }

        return $prepared;
    }
}
