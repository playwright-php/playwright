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

namespace Playwright\Configuration;

use Playwright\Browser\BrowserType;
use Psr\Log\LoggerInterface;

/**
 * Fluent builder for PlaywrightConfig.
 *
 * Defaults aim for sensible behavior:
 * - Browser: chromium
 * - Headless: true
 * - Timeout: 30s
 * - Node min version: 20.0.0
 */
final class PlaywrightConfigBuilder
{
    private ?string $nodePath = null;
    private string $minNodeVersion = '20.0.0';

    private BrowserType $browser = BrowserType::CHROMIUM;
    private ?string $channel = null;
    private bool $headless = true;
    private int $timeoutMs = 30_000;
    private int $slowMoMs = 0;

    /** @var array<int, string> */
    private array $args = [];

    /** @var array<string, string> */
    private array $env = [];

    private ?string $downloadsDir = null;
    private ?string $videosDir = null;

    private bool $tracingEnabled = false;
    private ?string $traceDir = null;
    private bool $traceScreenshots = true;
    private bool $traceSnapshots = true;

    /** @var array{server?: string, username?: string, password?: string, bypass?: string}|null */
    private ?array $proxy = null;

    private ?LoggerInterface $logger = null;

    public static function create(): self
    {
        return new self();
    }

    public static function fromEnv(): self
    {
        $b = new self();

        $get = static function (string $name): ?string {
            $v = getenv($name);

            return (false === $v || '' === $v) ? null : $v;
        };

        if ($node = $get('PLAYWRIGHT_NODE_PATH')) {
            $b->withNodePath($node);
        }
        if ($min = $get('PLAYWRIGHT_NODE_MIN_VERSION')) {
            $b->withMinNodeVersion($min);
        }

        if ($browser = $get('PW_BROWSER')) {
            $browser = strtolower($browser);
            $map = [
                'chromium' => BrowserType::CHROMIUM,
                'chrome' => BrowserType::CHROMIUM,
                'firefox' => BrowserType::FIREFOX,
                'webkit' => BrowserType::WEBKIT,
            ];
            if (isset($map[$browser])) {
                $b->withBrowser($map[$browser]);
            }
        }
        if ($channel = $get('PW_CHANNEL')) {
            $b->withChannel($channel);
        }
        if (($headless = $get('PW_HEADLESS')) !== null) {
            $b->withHeadless(self::strToBool($headless));
        }
        if (($timeout = $get('PW_TIMEOUT_MS')) !== null && ctype_digit($timeout)) {
            $b->withTimeoutMs((int) $timeout);
        }
        if (($slow = $get('PW_SLOWMO_MS')) !== null && ctype_digit($slow)) {
            $b->withSlowMoMs((int) $slow);
        }

        if (($trace = $get('PW_TRACING')) !== null) {
            $b->withTracing(self::strToBool($trace), $get('PW_TRACE_DIR') ?: null);
        }

        if ($dl = $get('PW_DOWNLOADS_DIR')) {
            $b->withDownloadsDir($dl);
        }
        if ($vd = $get('PW_VIDEOS_DIR')) {
            $b->withVideosDir($vd);
        }

        if ($proxy = $get('PW_PROXY_SERVER')) {
            $b->withProxy(
                server: $proxy,
                username: $get('PW_PROXY_USERNAME'),
                password: $get('PW_PROXY_PASSWORD'),
                bypass: $get('PW_PROXY_BYPASS')
            );
        }

        return $b;
    }

    public function withNodePath(?string $path): self
    {
        $this->nodePath = $path ?: null;

        return $this;
    }

    public function withMinNodeVersion(string $version): self
    {
        $this->minNodeVersion = $version;

        return $this;
    }

    public function withBrowser(BrowserType $browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    public function withChannel(?string $channel): self
    {
        $this->channel = $channel ?: null;

        return $this;
    }

    public function withHeadless(bool $headless): self
    {
        $this->headless = $headless;

        return $this;
    }

    public function withTimeoutMs(int $timeoutMs): self
    {
        $this->timeoutMs = max(0, $timeoutMs);

        return $this;
    }

    public function withSlowMoMs(int $slowMoMs): self
    {
        $this->slowMoMs = max(0, $slowMoMs);

        return $this;
    }

    /**
     * @param array<int, string> $args
     */
    public function withArgs(array $args): self
    {
        $this->args = array_values(array_unique($args));

        return $this;
    }

    public function addArg(string $arg): self
    {
        $this->args[] = $arg;
        $this->args = array_values(array_unique($this->args));

        return $this;
    }

    /**
     * @param array<string, string> $env
     */
    public function withEnv(array $env): self
    {
        $this->env = $env;

        return $this;
    }

    public function addEnv(string $key, string $value): self
    {
        $this->env[$key] = $value;

        return $this;
    }

    public function withDownloadsDir(?string $dir): self
    {
        $this->downloadsDir = $dir ?: null;

        return $this;
    }

    public function withVideosDir(?string $dir): self
    {
        $this->videosDir = $dir ?: null;

        return $this;
    }

    public function withTracing(bool $enabled, ?string $traceDir = null, bool $screenshots = true, bool $snapshots = true): self
    {
        $this->tracingEnabled = $enabled;
        $this->traceDir = $enabled ? ($traceDir ?: $this->traceDir) : null;
        $this->traceScreenshots = $enabled ? $screenshots : $this->traceScreenshots;
        $this->traceSnapshots = $enabled ? $snapshots : $this->traceSnapshots;

        return $this;
    }

    public function withProxy(?string $server, ?string $username = null, ?string $password = null, ?string $bypass = null): self
    {
        if (null === $server || '' === $server) {
            $this->proxy = null;

            return $this;
        }

        $this->proxy = array_filter([
            'server' => $server,
            'username' => $username ?: null,
            'password' => $password ?: null,
            'bypass' => $bypass ?: null,
        ], static fn ($v) => null !== $v);

        return $this;
    }

    public function withLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function build(): PlaywrightConfig
    {
        if ($this->timeoutMs < 0) {
            throw new \InvalidArgumentException('timeoutMs must be >= 0');
        }
        if ($this->slowMoMs < 0) {
            throw new \InvalidArgumentException('slowMoMs must be >= 0');
        }
        if ('' === $this->channel) {
            $this->channel = null;
        }
        if ('' === $this->downloadsDir) {
            $this->downloadsDir = null;
        }
        if ('' === $this->videosDir) {
            $this->videosDir = null;
        }
        if ($this->tracingEnabled && '' === $this->traceDir) {
            $this->traceDir = null;
        }

        return new PlaywrightConfig(
            nodePath: $this->nodePath,
            minNodeVersion: $this->minNodeVersion,
            browser: $this->browser,
            channel: $this->channel,
            headless: $this->headless,
            timeoutMs: $this->timeoutMs,
            slowMoMs: $this->slowMoMs,
            args: array_values($this->args),
            env: $this->env,
            downloadsDir: $this->downloadsDir,
            videosDir: $this->videosDir,
            tracingEnabled: $this->tracingEnabled,
            traceDir: $this->traceDir,
            traceScreenshots: $this->traceScreenshots,
            traceSnapshots: $this->traceSnapshots,
            proxy: $this->proxy,
            logger: $this->logger
        );
    }

    private static function strToBool(string $value): bool
    {
        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'y', 'on' => true,
            default => false,
        };
    }
}
