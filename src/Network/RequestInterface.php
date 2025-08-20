<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Network;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface RequestInterface
{
    public function url(): string;

    public function method(): string;

    public function headers(): array;

    public function postData(): ?string;

    public function postDataJSON(): ?array;

    public function resourceType(): string;
}
