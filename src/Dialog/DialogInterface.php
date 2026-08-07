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
 * Represents a page dialog.
 *
 * Dialogs expose alerts, confirms, prompts, and beforeunload messages.
 * Accept or dismiss them before the page can continue its dialog flow.
 */
interface DialogInterface
{
    /**
     * Returns the owning page.
     *
     * The page emitted the dialog event that created this object.
     * Use it to associate the dialog with the active browser context.
     */
    public function page(): PageInterface;

    /**
     * Returns the dialog type.
     *
     * Typical values include alert, confirm, prompt, and beforeunload.
     * The type determines whether prompt text has any effect when accepting.
     */
    public function type(): string;

    /**
     * Returns the dialog message.
     *
     * The browser supplies this text when opening the dialog.
     * Use it to distinguish dialogs emitted by the same page.
     */
    public function message(): string;

    /**
     * Returns the default prompt value.
     *
     * Prompt dialogs can prefill a value supplied by the page.
     * Other dialog types can return null.
     */
    public function defaultValue(): ?string;

    /**
     * Accepts the dialog.
     *
     * Supply prompt text to replace the default value for a prompt dialog.
     * The page resumes once the browser processes the acceptance.
     */
    public function accept(?string $promptText = null): void;

    /**
     * Dismisses the dialog.
     *
     * A confirm dialog resolves as cancelled and a prompt receives no value.
     * The page resumes once the browser processes the dismissal.
     */
    public function dismiss(): void;
}
