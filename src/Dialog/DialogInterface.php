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

namespace Playwright\Dialog;

use Playwright\Page\PageInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @method PageInterface page() The page that opened the dialog
 */
interface DialogInterface
{
    public function type(): string;

    public function message(): string;

    public function defaultValue(): ?string;

    public function accept(?string $promptText = null): void;

    public function dismiss(): void;
}
