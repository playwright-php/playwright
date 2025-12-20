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

namespace Playwright\WebSocket;

use Playwright\Event\EventDispatcherInterface;
use Playwright\Transport\TransportInterface;
use Playwright\WebSocket\Options\CloseOptions;

final class WebSocketRoute implements WebSocketRouteInterface, EventDispatcherInterface
{
    /**
     * @var callable(array{code?: int|null, reason?: string|null}): void|null
     */
    private $closeHandler;

    /**
     * @var callable(array{payload: string, direction?: string|null}): void|null
     */
    private $messageHandler;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $routeId,
        private readonly string $socketUrl,
    ) {
        if (\method_exists($this->transport, 'addEventDispatcher')) {
            $this->transport->addEventDispatcher($this->routeId, $this);
        }
    }

    public function url(): string
    {
        return $this->socketUrl;
    }

    /**
     * @param array<string, mixed>|CloseOptions $options
     */
    public function close(array|CloseOptions $options = []): void
    {
        $options = CloseOptions::from($options);
        $code = $options->code;
        $reason = $options->reason;

        $this->transport->sendAsync([
            'action' => 'websocketRoute.close',
            'routeId' => $this->routeId,
            'options' => ['code' => $code, 'reason' => $reason],
        ]);
    }

    public function connectToServer(): WebSocketRouteInterface
    {
        $response = $this->transport->send([
            'action' => 'websocketRoute.connectToServer',
            'routeId' => $this->routeId,
        ]);

        $serverRouteId = $response['serverRouteId'] ?? null;
        $url = $response['url'] ?? $this->socketUrl;

        if (\is_string($serverRouteId) && \is_string($url)) {
            return new self($this->transport, $serverRouteId, $url);
        }

        return $this;
    }

    public function onClose(callable $handler): void
    {
        $this->closeHandler = $handler;
        $this->transport->sendAsync([
            'action' => 'websocketRoute.onClose',
            'routeId' => $this->routeId,
        ]);
    }

    public function onMessage(callable $handler): void
    {
        $this->messageHandler = $handler;
        $this->transport->sendAsync([
            'action' => 'websocketRoute.onMessage',
            'routeId' => $this->routeId,
        ]);
    }

    public function send(string $message): void
    {
        $this->transport->sendAsync([
            'action' => 'websocketRoute.send',
            'routeId' => $this->routeId,
            'message' => $message,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function dispatchEvent(string $eventName, array $params): void
    {
        switch ($eventName) {
            case 'close':
                $handler = $this->closeHandler;
                if (\is_callable($handler)) {
                    $code = isset($params['code']) && \is_int($params['code']) ? $params['code'] : null;
                    $reason = isset($params['reason']) && \is_string($params['reason']) ? $params['reason'] : null;
                    $handler(['code' => $code, 'reason' => $reason]);
                }
                break;
            case 'message':
                $handler = $this->messageHandler;
                if (\is_callable($handler)) {
                    $payload = isset($params['payload']) && \is_string($params['payload']) ? $params['payload'] : '';
                    $direction = isset($params['direction']) && \is_string($params['direction']) ? $params['direction'] : null;
                    $handler(['payload' => $payload, 'direction' => $direction]);
                }
                break;
            default:
                break;
        }
    }
}
