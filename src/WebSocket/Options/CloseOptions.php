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

namespace Playwright\WebSocket\Options;

final readonly class CloseOptions
{
    public function __construct(
        public ?int $code = null,
        public ?string $reason = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $options = [];
        if (null !== $this->code) {
            $options['code'] = $this->code;
        }
        if (null !== $this->reason) {
            $options['reason'] = $this->reason;
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

        /** @var int|null $code */
        $code = $options['code'] ?? null;
        /** @var string|null $reason */
        $reason = $options['reason'] ?? null;

        return new self($code, $reason);
    }
}
