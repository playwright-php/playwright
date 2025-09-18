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

namespace Playwright\Network;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface RequestInterface
{
    public function url(): string;

    public function method(): string;

    /**
     * @return array<string, string>
     */
    public function headers(): array;

    public function postData(): ?string;

    /**
     * @return array<string, mixed>|null
     */
    public function postDataJSON(): ?array;

    public function resourceType(): string;
}
