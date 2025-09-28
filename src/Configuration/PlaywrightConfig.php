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
 * Immutable configuration value object for Playwright PHP.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class PlaywrightConfig
{
    /**
     * @param array<int, string>    $args
     * @param array<string, string> $env
     * @param array{
     *   server?: string,
     *   username?: string,
     *   password?: string,
     *   bypass?: string
     * }|null $proxy
     */
    public function __construct(
        public readonly ?string $nodePath = null,
        public readonly string $minNodeVersion = '20.0.0',

        public readonly BrowserType $browser = BrowserType::CHROMIUM,
        public readonly ?string $channel = null,
        public readonly bool $headless = true,

        public readonly int $timeoutMs = 30000,
        public readonly int $slowMoMs = 0,

        public readonly array $args = [],
        public readonly array $env = [],

        public readonly ?string $downloadsDir = null,
        public readonly ?string $videosDir = null,
        public readonly ?string $screenshotDir = null,

        public readonly bool $tracingEnabled = false,
        public readonly ?string $traceDir = null,
        public readonly bool $traceScreenshots = false,
        public readonly bool $traceSnapshots = false,

        public readonly ?array $proxy = null,

        public readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Get the effective screenshot directory.
     *
     * @return string The directory where screenshots should be saved
     */
    public function getScreenshotDirectory(): string
    {
        if (null !== $this->screenshotDir) {
            return $this->screenshotDir;
        }

        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'playwright';
    }

    /**
     * Return a new instance with the given node path.
     */
    public function withNodePath(?string $nodePath): self
    {
        return new PlaywrightConfig(
            nodePath: $nodePath,
            minNodeVersion: $this->minNodeVersion,
            browser: $this->browser,
            channel: $this->channel,
            headless: $this->headless,
            timeoutMs: $this->timeoutMs,
            slowMoMs: $this->slowMoMs,
            args: $this->args,
            env: $this->env,
            downloadsDir: $this->downloadsDir,
            videosDir: $this->videosDir,
            screenshotDir: $this->screenshotDir,
            tracingEnabled: $this->tracingEnabled,
            traceDir: $this->traceDir,
            traceScreenshots: $this->traceScreenshots,
            traceSnapshots: $this->traceSnapshots,
            proxy: $this->proxy,
            logger: $this->logger,
        );
    }

    /**
     * For debugging/logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nodePath' => $this->nodePath,
            'minNodeVersion' => $this->minNodeVersion,
            'browser' => $this->browser->value,
            'channel' => $this->channel,
            'headless' => $this->headless,
            'timeoutMs' => $this->timeoutMs,
            'slowMoMs' => $this->slowMoMs,
            'args' => $this->args,
            'env' => $this->env,
            'downloadsDir' => $this->downloadsDir,
            'videosDir' => $this->videosDir,
            'screenshotDir' => $this->screenshotDir,
            'effectiveScreenshotDir' => $this->getScreenshotDirectory(),
            'tracingEnabled' => $this->tracingEnabled,
            'traceDir' => $this->traceDir,
            'traceScreenshots' => $this->traceScreenshots,
            'traceSnapshots' => $this->traceSnapshots,
            'proxy' => $this->proxy,
            'loggerProvided' => null !== $this->logger,
        ];
    }
}
