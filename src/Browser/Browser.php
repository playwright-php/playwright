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

namespace Playwright\Browser;

use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\ProtocolErrorException;
use Playwright\Page\PageInterface;
use Playwright\Transport\TransportInterface;

final class Browser implements BrowserInterface
{
    private BrowserContextInterface $defaultContext;

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
            throw new ProtocolErrorException('Invalid contextId returned from transport', 0);
        }

        $context = new BrowserContext($this->transport, $response['contextId'], $this->config);
        $this->contexts[] = $context;

        return $context;
    }

    public function newPage(array $options = []): PageInterface
    {
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
