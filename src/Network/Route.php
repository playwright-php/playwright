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

use Playwright\Transport\TransportInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class Route implements RouteInterface
{
    private RequestInterface $request;

    /**
     * @param array<string, mixed> $requestData
     */
    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $routeId,
        array $requestData,
    ) {
        $this->request = new Request($requestData);
    }

    public function request(): RequestInterface
    {
        return $this->request;
    }

    public function abort(string $errorCode = 'failed'): void
    {
        $this->transport->sendAsync([
            'action' => 'route.abort',
            'routeId' => $this->routeId,
            'errorCode' => $errorCode,
        ]);
    }

    public function continue(?array $options = null): void
    {
        $this->transport->sendAsync([
            'action' => 'route.continue',
            'routeId' => $this->routeId,
            'options' => $options,
        ]);
    }

    public function fulfill(array $options): void
    {
        $this->transport->sendAsync([
            'action' => 'route.fulfill',
            'routeId' => $this->routeId,
            'options' => $options,
        ]);
    }
}
