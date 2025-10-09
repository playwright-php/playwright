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

/**
 * Fluent helper to build BrowserContext options arrays.
 *
 * This class does not change any runtime behavior. It only helps
 * produce the associative array expected by Browser::newContext([...])
 * and Playwright::chromium(['context' => [...]]).
 *
 * @author Simon André <hello@simonandre.ca>
 */
final class BrowserContextBuilder
{
    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    public static function create(): self
    {
        return new self();
    }

    public function withViewport(int $width, int $height): self
    {
        $this->options['viewport'] = ['width' => max(0, $width), 'height' => max(0, $height)];

        return $this;
    }

    public function withUserAgent(string $userAgent): self
    {
        $this->options['userAgent'] = $userAgent;

        return $this;
    }

    public function withLocale(string $locale): self
    {
        $this->options['locale'] = $locale;

        return $this;
    }

    public function withTimezoneId(string $timezoneId): self
    {
        $this->options['timezoneId'] = $timezoneId;

        return $this;
    }

    /**
     * @param array<string, mixed>|string $state Path to storage state file or state data
     */
    public function withStorageState(array|string $state): self
    {
        $this->options['storageState'] = $state;

        return $this;
    }

    public function withDownloadsPath(string $path): self
    {
        $this->options['acceptDownloads'] = true;
        $this->options['downloadsPath'] = $path;

        return $this;
    }

    public function withRecordVideoDir(string $dir): self
    {
        $this->options['recordVideo'] = ['dir' => $dir];

        return $this;
    }

    public function withColorScheme(string $scheme): self
    {
        $this->options['colorScheme'] = $scheme;

        return $this;
    }

    public function withDeviceScaleFactor(int|float $factor): self
    {
        $this->options['deviceScaleFactor'] = $factor;

        return $this;
    }

    public function withIsMobile(bool $isMobile = true): self
    {
        $this->options['isMobile'] = $isMobile;

        return $this;
    }

    public function withHasTouch(bool $hasTouch = true): self
    {
        $this->options['hasTouch'] = $hasTouch;

        return $this;
    }

    /**
     * @param array<int, string> $permissions
     */
    public function withPermissions(array $permissions): self
    {
        $this->options['permissions'] = array_values($permissions);

        return $this;
    }

    /**
     * Export as array suitable for Browser::newContext([...]).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->options;
    }
}
