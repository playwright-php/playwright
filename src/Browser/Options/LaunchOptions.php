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

namespace Playwright\Browser\Options;

final readonly class LaunchOptions
{
    /**
     * @param array{server: string, username?: string, password?: string, bypass?: string}|null $proxy
     * @param array<string>|null                                                                $args
     */
    public function __construct(
        public ?bool $headless = null,
        public ?int $slowMo = null,
        public ?string $channel = null,
        public ?string $executablePath = null,
        public ?array $proxy = null,
        public ?string $downloadsPath = null,
        public ?array $args = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        if (null !== $this->headless) {
            $options['headless'] = $this->headless;
        }
        if (null !== $this->slowMo) {
            $options['slowMo'] = $this->slowMo;
        }
        if (null !== $this->channel) {
            $options['channel'] = $this->channel;
        }
        if (null !== $this->executablePath) {
            $options['executablePath'] = $this->executablePath;
        }
        if (null !== $this->proxy) {
            $options['proxy'] = $this->proxy;
        }
        if (null !== $this->downloadsPath) {
            $options['downloadsPath'] = $this->downloadsPath;
        }
        if (null !== $this->args) {
            $options['args'] = $this->args;
        }

        return $options;
    }

    /**
     * @param array<string, mixed>|self $options
     */
    public static function from(array|self $options = []): self
    {
        if ($options instanceof self) {
            return $options;
        }

        /** @var bool|null $headless */
        $headless = $options['headless'] ?? null;
        /** @var int|null $slowMo */
        $slowMo = $options['slowMo'] ?? null;
        /** @var string|null $channel */
        $channel = $options['channel'] ?? null;
        /** @var string|null $executablePath */
        $executablePath = $options['executablePath'] ?? null;
        /** @var array{server: string, username?: string, password?: string, bypass?: string}|null $proxy */
        $proxy = $options['proxy'] ?? null;
        /** @var string|null $downloadsPath */
        $downloadsPath = $options['downloadsPath'] ?? null;
        /** @var array<string>|null $args */
        $args = $options['args'] ?? null;

        return new self($headless, $slowMo, $channel, $executablePath, $proxy, $downloadsPath, $args);
    }
}
