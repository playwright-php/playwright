<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Mocks;

use PlaywrightPHP\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Mock transport for testing without real process communication.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class MockProcessTransport implements TransportInterface
{
    private MockPlaywrightServer $server;
    private bool $connected = false;

    /** @var array<int, array<string, mixed>> */
    private array $sentMessages = [];

    public function __construct(?MockPlaywrightServer $server = null)
    {
        $this->server = $server ?? new MockPlaywrightServer();
    }

    public function connect(): void
    {
        $this->server->start();
        $this->connected = true;
    }

    public function disconnect(): void
    {
        $this->server->stop();
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->server->isRunning();
    }

    /**
     * @param array<string, mixed> $message
     *
     * @return array<string, mixed>
     */
    public function send(array $message): array
    {
        if (!$this->isConnected()) {
            throw new \RuntimeException('Transport not connected');
        }

        $this->sentMessages[] = $message;

        return $this->server->handleRequest($message);
    }

    /**
     * @param array<string, mixed> $message
     */
    public function sendAsync(array $message): void
    {
        $this->send($message);
    }

    public function processEvents(): void
    {
        // Mock implementation - no actual events to process
    }

    public function addEventDispatcher(EventDispatcherInterface $dispatcher): void
    {
        // Mock implementation - no actual events
    }

    /**
     * Get all messages that were sent through this transport.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSentMessages(): array
    {
        return $this->sentMessages;
    }

    /**
     * Clear all recorded sent messages.
     */
    public function clearSentMessages(): void
    {
        $this->sentMessages = [];
    }

    /**
     * Get the mock server instance.
     */
    public function getServer(): MockPlaywrightServer
    {
        return $this->server;
    }
}
