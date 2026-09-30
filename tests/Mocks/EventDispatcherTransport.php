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

namespace Playwright\Tests\Mocks;

use Playwright\Event\EventDispatcherInterface;
use Playwright\Transport\TransportInterface;

/**
 * Decorates a transport with the event dispatcher registry of JsonRpcTransport.
 */
final class EventDispatcherTransport implements TransportInterface
{
    /** @var array<string, EventDispatcherInterface> */
    private array $eventDispatchers = [];

    public function __construct(private readonly TransportInterface $decorated)
    {
    }

    public function addEventDispatcher(string $id, EventDispatcherInterface $dispatcher): void
    {
        $this->eventDispatchers[$id] = $dispatcher;
    }

    public function removeEventDispatcher(string $id): void
    {
        unset($this->eventDispatchers[$id]);
    }

    /**
     * @return array<string, EventDispatcherInterface>
     */
    public function getEventDispatchers(): array
    {
        return $this->eventDispatchers;
    }

    public function connect(): void
    {
        $this->decorated->connect();
    }

    public function disconnect(): void
    {
        $this->decorated->disconnect();
    }

    public function send(array $message): array
    {
        return $this->decorated->send($message);
    }

    public function sendAsync(array $message): void
    {
        $this->decorated->sendAsync($message);
    }

    public function isConnected(): bool
    {
        return $this->decorated->isConnected();
    }

    public function processEvents(): void
    {
        $this->decorated->processEvents();
    }
}
