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

namespace Playwright\Selector;

use Playwright\Selector\Options\RegisterOptions;
use Playwright\Transport\TransportInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class Selectors implements SelectorsInterface
{
    private string $testIdAttribute = 'data-testid';

    public function __construct(
        private readonly TransportInterface $transport,
    ) {
    }

    /**
     * @param array<string, mixed>|RegisterOptions $options
     */
    public function register(string $name, string $script, array|RegisterOptions $options = []): void
    {
        $options = RegisterOptions::from($options);
        $this->transport->send([
            'action' => 'selectors.register',
            'name' => $name,
            'script' => $script,
            'options' => $options->toArray(),
        ]);
    }

    public function setTestIdAttribute(string $attributeName): void
    {
        $this->testIdAttribute = $attributeName;

        $this->transport->send([
            'action' => 'selectors.setTestIdAttribute',
            'attributeName' => $attributeName,
        ]);
    }

    public function getTestIdAttribute(): string
    {
        return $this->testIdAttribute;
    }
}
