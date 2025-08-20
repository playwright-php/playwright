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
class ConsoleMessage implements ConsoleMessageInterface
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function type(): string
    {
        return $this->data['type'];
    }

    public function text(): string
    {
        return $this->data['text'];
    }

    public function args(): array
    {
        // This is a simplified implementation. A real implementation would
        // need to deserialize the JSHandles from the server.
        return $this->data['args'];
    }

    public function location(): array
    {
        return $this->data['location'];
    }
}
