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

use Playwright\Transport\TransportInterface;

final class APIRequest implements APIRequestInterface
{
    public function __construct(
        private readonly TransportInterface $transport,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function newContext(array $options = []): APIRequestContextInterface
    {
        $response = $this->transport->send([
            'action' => 'api.newContext',
            'options' => $options,
        ]);

        $contextId = $response['contextId'] ?? null;

        if (!is_string($contextId)) {
            throw new \RuntimeException('Failed to create API request context');
        }

        $baseURL = null;
        if (isset($options['baseURL']) && is_string($options['baseURL'])) {
            $baseURL = $options['baseURL'];
        }

        $shareCookies = false;
        if (isset($options['storageState']) && is_array($options['storageState'])) {
            $shareCookies = true;
        }

        return new APIRequestContext(
            $this->transport,
            $contextId,
            $baseURL,
            $shareCookies
        );
    }
}
