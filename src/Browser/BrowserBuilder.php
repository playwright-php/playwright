<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Browser;

use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\Exception\PlaywrightException;
use PlaywrightPHP\Transport\TransportInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class BrowserBuilder
{
    private array $launchOptions = [];

    public function __construct(
        private readonly string $browserType,
        private readonly TransportInterface $transport,
        private readonly LoggerInterface $logger,
        private readonly ?PlaywrightConfig $config = null,
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

    public function withArgs(array $args): self
    {
        $this->launchOptions['args'] = $args;

        return $this;
    }

    public function withInspector(): self
    {
        $this->launchOptions['env']['PWDEBUG'] = 'console';

        return $this;
    }

    public function launch(): BrowserInterface
    {
        $this->logger->info('Launching browser', [
            'browser' => $this->browserType,
            'options' => $this->launchOptions,
        ]);

        $response = $this->transport->send([
            'action' => 'launch',
            'browser' => $this->browserType,
            'options' => $this->launchOptions,
        ]);

        if (isset($response['error'])) {
            throw new PlaywrightException($response['error']);
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
