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
use Playwright\Exception\PlaywrightException;
use Playwright\Transport\Sanitizer;
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;

final class BrowserBuilder
{
    /**
     * @var array<string, mixed>
     */
    private array $launchOptions = [];

    public function __construct(
        private readonly string $browserType,
        private readonly TransportInterface $transport,
        private readonly LoggerInterface $logger,
        private readonly PlaywrightConfig $config,
    ) {
    }

    public function withHeadless(bool $headless = true): self
    {
        $this->launchOptions['headless'] = $headless;

        return $this;
    }

    public function withSlowMo(int $milliseconds): self
    {
        $this->launchOptions['slowMo'] = $milliseconds;

        return $this;
    }

    public function withChannel(string $channel): self
    {
        $this->launchOptions['channel'] = $channel;

        return $this;
    }

    /**
     * @param array{server: string, username?: string, password?: string, bypass?: string} $proxy
     */
    public function withProxy(array $proxy): self
    {
        $this->launchOptions['proxy'] = $proxy;

        return $this;
    }

    public function withDownloadsPath(string $path): self
    {
        $this->launchOptions['downloadsPath'] = $path;

        return $this;
    }

    /**
     * @param array<string> $args
     */
    public function withArgs(array $args): self
    {
        $this->launchOptions['args'] = $args;

        return $this;
    }

    public function withInspector(): self
    {
        if (!isset($this->launchOptions['env'])) {
            $this->launchOptions['env'] = [];
        }
        if (!is_array($this->launchOptions['env'])) {
            $this->launchOptions['env'] = [];
        }
        $this->launchOptions['env']['PWDEBUG'] = 'console';

        return $this;
    }

    public function launch(): BrowserInterface
    {
        // Launch options can carry proxy credentials, so never log them raw.
        $this->logger->info('Launching browser', [
            'browser' => $this->browserType,
            'options' => Sanitizer::sanitizeParams($this->launchOptions),
        ]);

        $response = $this->transport->send([
            'action' => 'launch',
            'browser' => $this->browserType,
            'options' => $this->launchOptions,
            // The server creates the default context during launch, so its
            // options have to travel with the launch command.
            'contextOptions' => $this->config->toContextOptions(),
        ]);

        if (isset($response['error'])) {
            if (!is_string($response['error'])) {
                throw new PlaywrightException('Browser launch failed with unknown error');
            }
            throw new PlaywrightException($response['error']);
        }

        if (!is_string($response['browserId'])) {
            throw new PlaywrightException('Invalid browserId returned from transport');
        }
        if (!is_string($response['defaultContextId'])) {
            throw new PlaywrightException('Invalid defaultContextId returned from transport');
        }
        if (!is_string($response['version'])) {
            throw new PlaywrightException('Invalid version returned from transport');
        }

        return new Browser(
            $this->transport,
            $response['browserId'],
            $response['defaultContextId'],
            $response['version'],
            $this->config
        );
    }

    /**
     * Attaches Playwright to an existing BrowserServer via WebSocket endpoint.
     *
     * @param array<string, mixed> $options
     */
    public function connect(string $wsEndpoint, array $options = []): BrowserInterface
    {
        $this->logger->info('Connecting to browser server', [
            'browser' => $this->browserType,
            'wsEndpoint' => Sanitizer::sanitizeUrl($wsEndpoint),
            'options' => Sanitizer::sanitizeParams($options),
        ]);

        $response = $this->transport->send([
            'action' => 'connect',
            'browser' => $this->browserType,
            'wsEndpoint' => $wsEndpoint,
            'options' => $options,
        ]);

        if (isset($response['error'])) {
            if (!is_string($response['error'])) {
                throw new PlaywrightException('Browser connect failed with unknown error');
            }
            throw new PlaywrightException($response['error']);
        }

        if (!is_string($response['browserId'])) {
            throw new PlaywrightException('Invalid browserId returned from transport');
        }
        if (!is_string($response['defaultContextId'])) {
            throw new PlaywrightException('Invalid defaultContextId returned from transport');
        }
        if (!is_string($response['version'])) {
            throw new PlaywrightException('Invalid version returned from transport');
        }

        return new Browser(
            $this->transport,
            $response['browserId'],
            $response['defaultContextId'],
            $response['version'],
            $this->config
        );
    }

    /**
     * Attaches Playwright over CDP (Chromium only).
     *
     * @param array<string, mixed> $options
     */
    public function connectOverCDP(string $endpointURL, array $options = []): BrowserInterface
    {
        $this->logger->info('Connecting over CDP', [
            'browser' => $this->browserType,
            'endpointURL' => Sanitizer::sanitizeUrl($endpointURL),
            'options' => Sanitizer::sanitizeParams($options),
        ]);

        $response = $this->transport->send([
            'action' => 'connectOverCDP',
            'browser' => $this->browserType,
            'endpointURL' => $endpointURL,
            'options' => $options,
        ]);

        if (isset($response['error'])) {
            if (!is_string($response['error'])) {
                throw new PlaywrightException('Browser connectOverCDP failed with unknown error');
            }
            throw new PlaywrightException($response['error']);
        }

        if (!is_string($response['browserId'])) {
            throw new PlaywrightException('Invalid browserId returned from transport');
        }
        if (!is_string($response['defaultContextId'])) {
            throw new PlaywrightException('Invalid defaultContextId returned from transport');
        }
        if (!is_string($response['version'])) {
            throw new PlaywrightException('Invalid version returned from transport');
        }

        return new Browser(
            $this->transport,
            $response['browserId'],
            $response['defaultContextId'],
            $response['version'],
            $this->config
        );
    }
}
