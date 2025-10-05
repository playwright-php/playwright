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

namespace Playwright;

use Playwright\Browser\BrowserBuilder;
use Playwright\Configuration\PlaywrightConfig;
use Playwright\Exception\DisconnectedException;
use Playwright\Exception\ProcessCrashedException;
use Playwright\Exception\ProcessLaunchException;
use Playwright\Exception\TransportException;
use Playwright\Selector\Selectors;
use Playwright\Selector\SelectorsInterface;
use Playwright\Transport\TransportInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class PlaywrightClient
{
    private bool $isConnected = false;
    private ?SelectorsInterface $selectors = null;

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly LoggerInterface $logger,
        private readonly PlaywrightConfig $config,
    ) {
    }

    public function chromium(): BrowserBuilder
    {
        $this->connect();
        $this->logger->debug('Creating Chromium browser builder');

        return $this->createBrowserBuilder('chromium');
    }

    public function firefox(): BrowserBuilder
    {
        $this->connect();
        $this->logger->debug('Creating Firefox browser builder');

        return $this->createBrowserBuilder('firefox');
    }

    public function webkit(): BrowserBuilder
    {
        $this->connect();
        $this->logger->debug('Creating WebKit browser builder');

        return $this->createBrowserBuilder('webkit');
    }

    public function selectors(): SelectorsInterface
    {
        $this->connect();

        if (null === $this->selectors) {
            $this->selectors = new Selectors($this->transport);
        }

        return $this->selectors;
    }

    public function close(): void
    {
        if (!$this->isConnected) {
            return;
        }

        $this->logger->debug('Closing Playwright client connection');

        try {
            $this->transport->disconnect();
            $this->isConnected = false;
            $this->logger->info('Successfully closed Playwright client connection');
        } catch (\Throwable $e) {
            $this->logger->warning('Error while closing Playwright client connection', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            $this->isConnected = false;
        }
    }

    private function connect(): void
    {
        if ($this->isConnected) {
            return;
        }

        $this->logger->debug('Connecting to Playwright server');

        try {
            $this->transport->connect();
            $this->isConnected = true;
            $this->logger->info('Successfully connected to Playwright server');
        } catch (ProcessLaunchException|ProcessCrashedException $e) {
            $this->logger->error('Failed to launch or connect to Playwright server', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        } catch (TransportException $e) {
            $this->logger->error('Failed to connect to Playwright server', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw new DisconnectedException('Failed to connect to Playwright server', 0, $e);
        } catch (\Throwable $e) {
            $this->logger->error('An unexpected error occurred while connecting to the Playwright server', [
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    private function createBrowserBuilder(string $browserType): BrowserBuilder
    {
        $builder = new BrowserBuilder($browserType, $this->transport, $this->logger, $this->config);

        $builder->withHeadless($this->config->headless);

        if ($this->config->slowMoMs > 0) {
            $builder->withSlowMo($this->config->slowMoMs);
        }

        if (!empty($this->config->args)) {
            $builder->withArgs($this->config->args);
        }

        return $builder;
    }

    public function __destruct()
    {
        $this->close();
    }
}
