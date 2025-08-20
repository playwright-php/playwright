<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Configuration;

/**
 * Configuration builder with fluent interface.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
class ConfigurationBuilder
{
    private array $config = [
        'transport' => 'process',
        'timeout' => 30000,
        'debug' => false,
        'headless' => true,
        'args' => [],
        'env' => [],
        'async' => true, // Default to async
        'verbose' => false,
        'nodePath' => null,
        'serverScriptPath' => null,
        'cwd' => null,
    ];

    public static function create(): self
    {
        return new self();
    }

    public function withAsync(bool $async): self
    {
        $this->config['async'] = $async;

        return $this;
    }

    public function withVerboseLogging(bool $verbose = true): self
    {
        $this->config['verbose'] = $verbose;

        return $this;
    }

    public function withNodePath(string $path): self
    {
        $this->config['nodePath'] = $path;

        return $this;
    }

    public function withTransport(string $transport): self
    {
        $this->config['transport'] = $transport;

        return $this;
    }

    public function withTimeout(int $milliseconds): self
    {
        $this->config['timeout'] = $milliseconds;

        return $this;
    }

    public function withDebug(bool $debug = true): self
    {
        $this->config['debug'] = $debug;

        return $this;
    }

    public function withHeadless(bool $headless = true): self
    {
        $this->config['headless'] = $headless;

        return $this;
    }

    public function withArgs(array $args): self
    {
        $this->config['args'] = array_merge($this->config['args'], $args);

        return $this;
    }

    public function withEnv(array $env): self
    {
        $this->config['env'] = array_merge($this->config['env'], $env);

        return $this;
    }

    public function build(): PlaywrightConfig
    {
        return new PlaywrightConfig(
            transport: $this->config['transport'],
            nodePath: $this->config['nodePath'],
            timeout: $this->config['timeout'],
            headless: $this->config['headless'],
            debug: $this->config['debug'],
            args: $this->config['args'],
            env: $this->config['env'],
            async: $this->config['async'],
            verbose: $this->config['verbose'],
            serverScriptPath: $this->config['serverScriptPath'],
            cwd: $this->config['cwd'],
        );
    }
}
