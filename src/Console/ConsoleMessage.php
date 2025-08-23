<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Console;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
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
            throw new \RuntimeException('Invalid console message type');
        }

        return $type;
    }

    public function text(): string
    {
        $text = $this->data['text'];
        if (!is_string($text)) {
            throw new \RuntimeException('Invalid console message text');
        }

        return $text;
    }

    /**
     * @return array<mixed>
     */
    public function args(): array
    {
        // This is a simplified implementation. A real implementation would
        // need to deserialize the JSHandles from the server.
        $args = $this->data['args'];
        if (!is_array($args)) {
            throw new \RuntimeException('Invalid console message args');
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
            throw new \RuntimeException('Invalid console message location');
        }

        // PHPStan hint: after validation, this is array<string, mixed>
        /* @var array<string, mixed> $location */
        return $location;
    }
}
