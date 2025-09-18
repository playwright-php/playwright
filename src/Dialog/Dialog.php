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
 */
class Dialog
{
    public function __construct(
        private readonly PageInterface $page,
        private readonly string $dialogId,
        private readonly string $type,
        private readonly string $message,
        private readonly ?string $defaultValue,
    ) {
    }

    public function type(): string
    {
        return $this->type;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function defaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function accept(?string $promptText = null): void
    {
        $this->page->handleDialog($this->dialogId, true, $promptText);
    }

    public function dismiss(): void
    {
        $this->page->handleDialog($this->dialogId, false);
    }
}
