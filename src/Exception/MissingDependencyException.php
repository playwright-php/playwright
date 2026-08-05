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

namespace Playwright\Exception;

/**
 * Thrown when the Playwright server or its browsers have not been installed.
 *
 * These are setup problems rather than runtime failures, so the message always names the
 * command that fixes them.
 */
class MissingDependencyException extends RuntimeException
{
    public const INSTALL_COMMAND = 'vendor/bin/playwright-install --browsers';
    public const WITH_DEPENDENCIES_COMMAND = 'vendor/bin/playwright-install --with-deps';

    /**
     * The Node server could not load the "playwright" package, i.e. the server's dependencies
     * were never installed.
     */
    public static function server(?string $details = null): self
    {
        $message = \sprintf(
            'The Playwright server is not installed. Run "%s" to install it.',
            self::INSTALL_COMMAND,
        );

        if (null !== $details && '' !== $details) {
            $message .= "\n\n".$details;
        }

        return new self($message);
    }

    /**
     * Playwright is installed but the browser binaries it needs are missing.
     */
    public static function browsers(string $details): self
    {
        return new self(\sprintf(
            "The requested browser is not installed. Run \"%s\" to download it.\n\n%s",
            self::INSTALL_COMMAND,
            $details,
        ));
    }

    /**
     * The browsers are installed but the host is missing libraries they link against. Playwright
     * runs this check on Linux and Windows only.
     */
    public static function hostLibraries(string $details): self
    {
        return new self(\sprintf(
            "The host is missing system libraries the browsers need. Run \"%s\" to install them.\n\n%s",
            self::WITH_DEPENDENCIES_COMMAND,
            $details,
        ));
    }
}
