<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Browser;

use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Page\PageInterface;
use PlaywrightPHP\Transport\TransportInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class Browser implements BrowserInterface
{
    private ?BrowserContextInterface $defaultContext = null;

    /**
     * @var array<BrowserContextInterface>
     */
    private array $contexts = [];

    private bool $isConnected = true;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $browserId,
        private readonly string $defaultContextId,
        private readonly string $version,
        private readonly ?PlaywrightConfig $config = null,
    ) {
        $this->defaultContext = new BrowserContext($this->transport, $this->defaultContextId, $this->config);
        $this->contexts[] = $this->defaultContext;
    }

    public function context(): BrowserContextInterface
    {
        if (null === $this->defaultContext) {
            throw new \RuntimeException('Default context is not available');
        }

        return $this->defaultContext;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function newContext(array $options = []): BrowserContextInterface
    {
        $response = $this->transport->send([
            'action' => 'newContext',
            'browserId' => $this->browserId,
            'options' => $options,
        ]);

        if (!is_string($response['contextId'])) {
            throw new \RuntimeException('Invalid contextId returned from transport');
        }

        $context = new BrowserContext($this->transport, $response['contextId'], $this->config);
        $this->contexts[] = $context;

        return $context;
    }

    public function newPage(array $options = []): PageInterface
    {
        if (null === $this->defaultContext) {
            $this->defaultContext = new BrowserContext($this->transport, $this->defaultContextId, $this->config);
        }

        return $this->defaultContext->newPage($options);
    }

    public function close(): void
    {
        $this->transport->send([
            'action' => 'close',
            'browserId' => $this->browserId,
        ]);

        $this->isConnected = false;
    }

    public function contexts(): array
    {
        return $this->contexts;
    }

    public function isConnected(): bool
    {
        return $this->isConnected && $this->transport->isConnected();
    }

    public function version(): string
    {
        return $this->version;
    }
}
