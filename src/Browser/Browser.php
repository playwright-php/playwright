<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Browser;

use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Exception\ProtocolErrorException;
use PlaywrightPHP\Exception\RuntimeException;
use PlaywrightPHP\Internal\OwnershipRegistry;
use PlaywrightPHP\Internal\RemoteObject;
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
    private RemoteObject $remoteObject;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $browserId,
        private readonly string $defaultContextId,
        private readonly string $version,
        private readonly ?PlaywrightConfig $config = null,
    ) {
        $this->remoteObject = new BrowserRemoteObject($this->transport, $this->browserId, 'browser');
        OwnershipRegistry::register($this->remoteObject);
        
        $this->defaultContext = new BrowserContext($this->transport, $this->defaultContextId, $this->config);
        $this->contexts[] = $this->defaultContext;
        
        // Link default context as child (use instanceof check to be safe)
        if ($this->defaultContext instanceof BrowserContext && method_exists($this->defaultContext, 'getRemoteObject')) {
            OwnershipRegistry::linkParentChild($this->remoteObject, $this->defaultContext->getRemoteObject());
        }
    }

    public function context(): BrowserContextInterface
    {
        if (null === $this->defaultContext) {
            throw new RuntimeException('Default context is not available');
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
            throw new ProtocolErrorException('Invalid contextId returned from transport', 0);
        }

        $context = new BrowserContext($this->transport, $response['contextId'], $this->config);
        $this->contexts[] = $context;

        // Link context as child (use instanceof check to be safe)
        if ($context instanceof BrowserContext && method_exists($context, 'getRemoteObject')) {
            OwnershipRegistry::linkParentChild($this->remoteObject, $context->getRemoteObject());
        }

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
        if (!$this->isConnected) {
            return; // Already closed
        }

        $this->remoteObject->dispose();
    }

    public function isDisposed(): bool
    {
        return $this->remoteObject->isDisposed();
    }

    public function getRemoteObject(): RemoteObject
    {
        return $this->remoteObject;
    }

    public function contexts(): array
    {
        return $this->contexts;
    }

    public function isConnected(): bool
    {
        return !$this->remoteObject->isDisposed() && $this->transport->isConnected();
    }

    public function version(): string
    {
        return $this->version;
    }
}

/**
 * RemoteObject implementation for Browser.
 */
class BrowserRemoteObject extends RemoteObject
{
    protected function onDispose(): void
    {
        $this->transport->send([
            'action' => 'close',
            'browserId' => $this->remoteId,
        ]);
    }
}
