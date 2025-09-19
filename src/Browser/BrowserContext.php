<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Browser;

use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Event\EventDispatcherInterface;
use PlaywrightPHP\Exception\ProtocolErrorException;
use PlaywrightPHP\Exception\TimeoutException;
use PlaywrightPHP\Exception\TransportException;
use PlaywrightPHP\Internal\OwnershipRegistry;
use PlaywrightPHP\Internal\RemoteObject;
use PlaywrightPHP\Network\NetworkThrottling;
use PlaywrightPHP\Network\Route;
use PlaywrightPHP\Page\Page;
use PlaywrightPHP\Page\PageInterface;
use PlaywrightPHP\Transport\TransportInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class BrowserContext implements BrowserContextInterface, EventDispatcherInterface
{
    /**
     * @var array<string, PageInterface>
     */
    private array $pages = [];

    /**
     * @var array<array{url: string, handler: callable}>
     */
    private array $routeHandlers = [];

    /**
     * @var array<string, mixed>
     */
    private array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    private array $functions = [];

    private RemoteObject $remoteObject;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly string $contextId,
        private readonly ?PlaywrightConfig $config = null,
    ) {
        $this->remoteObject = new BrowserContextRemoteObject($this->transport, $this->contextId, 'context');
        OwnershipRegistry::register($this->remoteObject);
        
        if (method_exists($this->transport, 'addEventDispatcher')) {
            $this->transport->addEventDispatcher($this->contextId, $this);
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    public function dispatchEvent(string $eventName, array $params): void
    {
        if ('route' === $eventName) {
            if (!is_string($params['routeId'])) {
                throw new ProtocolErrorException('Invalid routeId in route event', 0);
            }
            if (!is_array($params['request'])) {
                throw new ProtocolErrorException('Invalid request data in route event', 0);
            }
            $route = new Route(
                $this->transport,
                $params['routeId'],
                $this->validateTransportArray($params['request'], 'request')
            );
            foreach ($this->routeHandlers as $handler) {
                if (fnmatch($handler['url'], $route->request()->url())) {
                    $handler['handler']($route);

                    return;
                }
            }
            $route->continue();
        }

        if (in_array($eventName, ['page', 'popup', 'pageCreated'], true)) {
            $pageId = $params['pageId'] ?? null;
            if (is_string($pageId) && !isset($this->pages[$pageId])) {
                $page = new Page($this->transport, $this, $pageId, $this->config);
                $this->pages[$pageId] = $page;
                
                // Link page as child (use instanceof check to be safe)
                if ($page instanceof Page && method_exists($page, 'getRemoteObject')) {
                    OwnershipRegistry::linkParentChild($this->remoteObject, $page->getRemoteObject());
                }
            }

            return;
        }

        if (in_array($eventName, ['pageClosed', 'page-closed'], true)) {
            $pageId = $params['pageId'] ?? null;
            if (is_string($pageId)) {
                unset($this->pages[$pageId]);
            }

            return;
        }

        if ('binding' === $eventName) {
            $bindingName = $params['name'];
            if (is_string($bindingName) && isset($this->bindings[$bindingName]) && is_callable($this->bindings[$bindingName])) {
                if (!is_string($params['pageId'])) {
                    throw new ProtocolErrorException('Invalid pageId in binding event', 0);
                }
                $source = [
                    'context' => $this,
                    'page' => $this->pages[$params['pageId']] ?? null,
                ];
                $result = $this->bindings[$bindingName]($source, ...$params['args']);
                $this->transport->sendAsync([
                    'action' => 'context.resolveBinding',
                    'bindingId' => $params['bindingId'],
                    'result' => $result,
                ]);
            }
        }

        if ('function' === $eventName) {
            $functionName = $params['name'];
            if (is_string($functionName) && isset($this->functions[$functionName]) && is_callable($this->functions[$functionName])) {
                $args = $params['args'];
                if (!is_array($args)) {
                    return;
                }
                $result = $this->functions[$functionName](...$args);
                $this->transport->sendAsync([
                    'action' => 'context.resolveFunction',
                    'functionId' => $params['functionId'],
                    'result' => $result,
                ]);
            }
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function newPage(array $options = []): PageInterface
    {
        $response = $this->transport->send([
            'action' => 'context.newPage',
            'contextId' => $this->contextId,
            'options' => $options,
        ]);

        if (isset($response['error'])) {
            $errorMsg = is_string($response['error']) ? $response['error'] : 'Unknown transport error';
            throw new TransportException('Transport error in newPage: '.$errorMsg);
        }

        if (!isset($response['pageId']) || !is_string($response['pageId'])) {
            throw new ProtocolErrorException('No valid pageId returned from transport in newPage', 0);
        }

        $page = new Page($this->transport, $this, $response['pageId'], $this->config);
        $this->pages[$response['pageId']] = $page;

        // Link page as child (use instanceof check to be safe)
        if ($page instanceof Page && method_exists($page, 'getRemoteObject')) {
            OwnershipRegistry::linkParentChild($this->remoteObject, $page->getRemoteObject());
        }

        return $page;
    }

    public function clipboardText(): string
    {
        $response = $this->transport->send([
            'action' => 'context.clipboardText',
            'contextId' => $this->contextId,
        ]);

        if (!is_string($response['value'])) {
            throw new ProtocolErrorException('Invalid clipboard text response', 0);
        }

        return $response['value'];
    }

    public function close(): void
    {
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

    /**
     * @param array<array<string, mixed>> $cookies
     */
    public function addCookies(array $cookies): void
    {
        $this->transport->send([
            'action' => 'context.addCookies',
            'contextId' => $this->contextId,
            'cookies' => $cookies,
        ]);
    }

    public function addInitScript(string $script): void
    {
        $this->transport->send([
            'action' => 'context.addInitScript',
            'contextId' => $this->contextId,
            'script' => $script,
        ]);
    }

    public function clearCookies(): void
    {
        $this->transport->send([
            'action' => 'context.clearCookies',
            'contextId' => $this->contextId,
        ]);
    }

    public function deleteCookie(string $name): void
    {
        $cookies = $this->cookies();
        if (empty($cookies)) {
            return;
        }

        $toDelete = [];
        foreach ($cookies as $cookie) {
            if (!isset($cookie['name']) || !is_string($cookie['name'])) {
                continue;
            }
            if ($cookie['name'] !== $name) {
                continue;
            }

            $domain = $cookie['domain'] ?? null;
            $path = $cookie['path'] ?? null;
            if (!is_string($domain) || !is_string($path)) {
                continue;
            }

            $toDelete[] = [
                'name' => $name,
                'value' => '',
                'domain' => $domain,
                'path' => $path,
                'expires' => 0,
            ];
        }

        if (empty($toDelete)) {
            return;
        }

        $this->transport->send([
            'action' => 'context.addCookies',
            'contextId' => $this->contextId,
            'cookies' => $toDelete,
        ]);
    }

    public function clearPermissions(): void
    {
        $this->transport->send([
            'action' => 'context.clearPermissions',
            'contextId' => $this->contextId,
        ]);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function cookies(?array $urls = null): array
    {
        $response = $this->transport->send([
            'action' => 'context.cookies',
            'contextId' => $this->contextId,
            'urls' => $urls,
        ]);

        if (!is_array($response['cookies'])) {
            throw new ProtocolErrorException('Invalid cookies response', 0);
        }

        /** @phpstan-var array<array<string, mixed>> $cookies */
        $cookies = $response['cookies'];

        return $cookies;
    }

    public function exposeBinding(string $name, callable $callback): void
    {
        $this->bindings[$name] = $callback;
        $this->transport->send([
            'action' => 'context.exposeBinding',
            'contextId' => $this->contextId,
            'name' => $name,
        ]);
    }

    public function exposeFunction(string $name, callable $callback): void
    {
        $this->functions[$name] = $callback;
        $this->transport->send([
            'action' => 'context.exposeFunction',
            'contextId' => $this->contextId,
            'name' => $name,
        ]);
    }

    /**
     * @param array<string> $permissions
     */
    public function grantPermissions(array $permissions): void
    {
        $this->transport->send([
            'action' => 'context.grantPermissions',
            'contextId' => $this->contextId,
            'permissions' => $permissions,
        ]);
    }

    /**
     * @return array<PageInterface>
     */
    public function pages(): array
    {
        return array_values($this->pages);
    }

    /**
     * @return array<string, mixed>
     */
    public function storageState(?string $path = null): array
    {
        $response = $this->transport->send([
            'action' => 'context.storageState',
            'contextId' => $this->contextId,
            'options' => $path ? ['path' => $path] : [],
        ]);

        if (!is_array($response['storageState'])) {
            throw new ProtocolErrorException('Invalid storageState response', 0);
        }

        /** @phpstan-var array<string, mixed> $storageState */
        $storageState = $response['storageState'];

        return $storageState;
    }

    public function getStorageState(): StorageState
    {
        $data = $this->storageState();

        return StorageState::fromArray($data);
    }

    public function setStorageState(StorageState $storageState): void
    {
        $this->transport->send([
            'action' => 'context.setStorageState',
            'contextId' => $this->contextId,
            'storageState' => $storageState->toArray(),
        ]);
    }

    public function saveStorageState(string $filePath): void
    {
        $storageState = $this->getStorageState();
        $storageState->saveToFile($filePath);
    }

    public function loadStorageState(string $filePath): void
    {
        $storageState = StorageState::fromFile($filePath);
        $this->setStorageState($storageState);
    }

    public function setGeolocation(?float $latitude, ?float $longitude, ?float $accuracy = null): void
    {
        $this->transport->send([
            'action' => 'context.setGeolocation',
            'contextId' => $this->contextId,
            'geolocation' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
            ],
        ]);
    }

    public function setOffline(bool $offline): void
    {
        $this->transport->send([
            'action' => 'context.setOffline',
            'contextId' => $this->contextId,
            'offline' => $offline,
        ]);
    }

    public function route(string $url, callable $handler): void
    {
        $this->transport->send([
            'action' => 'context.route',
            'contextId' => $this->contextId,
            'url' => $url,
        ]);
        $this->routeHandlers[] = ['url' => $url, 'handler' => $handler];
    }

    public function unroute(string $url, ?callable $handler = null): void
    {
        $this->routeHandlers = array_filter($this->routeHandlers, fn ($h) => $h['url'] !== $url);
        $this->transport->send([
            'action' => 'context.unroute',
            'contextId' => $this->contextId,
            'url' => $url,
        ]);
    }

    public function getEnv(string $name): ?string
    {
        $response = $this->transport->send([
            'action' => 'getEnv',
            'name' => $name,
        ]);

        $value = $response['value'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function startTracing(PageInterface $page, array $options = []): void
    {
        $this->transport->send([
            'action' => 'context.startTracing',
            'contextId' => $this->contextId,
            'pageId' => $page->getPageIdForTransport(),
            'options' => $options,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function waitForEvent(string $event, ?callable $predicate = null, ?int $timeout = null): array
    {
        return $this->transport->send([
            'action' => 'context.waitForEvent',
            'contextId' => $this->contextId,
            'event' => $event,
            'timeout' => $timeout,
        ]);
    }

    public function stopTracing(PageInterface $page, string $path): void
    {
        $this->transport->send([
            'action' => 'context.stopTracing',
            'contextId' => $this->contextId,
            'pageId' => $page->getPageIdForTransport(),
            'path' => $path,
        ]);
    }

    public function setNetworkThrottling(NetworkThrottling $throttling): void
    {
        $this->transport->send([
            'action' => 'context.setNetworkThrottling',
            'contextId' => $this->contextId,
            'throttling' => $throttling->toArray(),
        ]);
    }

    public function disableNetworkThrottling(): void
    {
        $this->setNetworkThrottling(NetworkThrottling::none());
    }

    /**
     * @param array<string, mixed> $options
     */
    public function waitForPopup(callable $action, array $options = []): PageInterface
    {
        $timeout = $options['timeout'] ?? 30000;
        $requestId = uniqid('popup_', true);

        if (method_exists($this->transport, 'storePendingCallback')) {
            $this->transport->storePendingCallback($requestId, $action);
        } else {
            // Fallback for transports that don't support callbacks
            $action();
        }

        $response = $this->transport->send([
            'action' => 'context.waitForPopup',
            'contextId' => $this->contextId,
            'timeout' => $timeout,
            'requestId' => $requestId,
        ]);

        $popupPageId = $response['popupPageId'] ?? null;
        if (!is_string($popupPageId)) {
            throw new TimeoutException('No popup was created within the timeout period');
        }

        $page = new Page($this->transport, $this, $popupPageId, $this->config);
        $this->pages[$popupPageId] = $page;

        // Link page as child (use instanceof check to be safe)
        if ($page instanceof Page && method_exists($page, 'getRemoteObject')) {
            OwnershipRegistry::linkParentChild($this->remoteObject, $page->getRemoteObject());
        }

        return $page;
    }

    /**
     * Helper method to validate and cast transport data to proper array type.
     *
     * @return array<string, mixed>
     */
    private function validateTransportArray(mixed $data, string $context = ''): array
    {
        if (!is_array($data)) {
            throw new ProtocolErrorException("Invalid {$context} data in transport response", 0);
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                throw new ProtocolErrorException("Invalid {$context} payload: non-string key in transport response", 0);
            }
            $result[$key] = $value;
        }

        return $result;
    }
}

/**
 * RemoteObject implementation for BrowserContext.
 */
class BrowserContextRemoteObject extends RemoteObject
{
    protected function onDispose(): void
    {
        $this->transport->send([
            'action' => 'context.close',
            'contextId' => $this->remoteId,
        ]);
    }
}
