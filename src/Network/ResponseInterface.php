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
interface ResponseInterface
{
    public function url(): string;

    public function status(): int;

    public function statusText(): string;

    public function ok(): bool;

    /**
     * @return array<string, string>
     */
    public function headers(): array;

    public function body(): string;

    public function text(): string;

    /**
     * @return array<string, mixed>
     */
    public function json(): array;
}
