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

namespace Playwright\Console;

use Playwright\Exception\ProtocolErrorException;

final class ConsoleMessage
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function type(): string
    {
        $type = $this->data['type'];
        if (!is_string($type)) {
            throw new ProtocolErrorException('Invalid console message type', 0);
        }

        return $type;
    }

    public function text(): string
    {
        $text = $this->data['text'];
        if (!is_string($text)) {
            throw new ProtocolErrorException('Invalid console message text', 0);
        }

        return $text;
    }

    /**
     * @return array<mixed>
     */
    public function args(): array
    {
        $args = $this->data['args'];
        if (!is_array($args)) {
            throw new ProtocolErrorException('Invalid console message args', 0);
        }

        return $args;
    }

    /**
     * @return array<string, mixed>
     */
    public function location(): array
    {
        $location = $this->data['location'];
        if (!is_array($location)) {
            throw new ProtocolErrorException('Invalid console message location', 0);
        }

        $result = [];
        foreach ($location as $key => $value) {
            if (!is_string($key)) {
                throw new ProtocolErrorException('Invalid console message location: non-string key', 0);
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
