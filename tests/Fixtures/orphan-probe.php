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

/*
 * Probe for OrphanedBrowserTest. Launches a browser, announces it is ready, then
 * blocks. The test kills this process without warning and checks that the Node
 * bridge and the browser die with it.
 *
 * Nothing here may register a shutdown hook: the point is to reproduce a process
 * that never gets the chance to clean up after itself.
 *
 * Usage: php orphan-probe.php <ready-file>
 */

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Playwright\Playwright;

$readyFile = $argv[1] ?? null;

if (!is_string($readyFile) || '' === $readyFile) {
    fwrite(\STDERR, "usage: orphan-probe.php <ready-file>\n");
    exit(1);
}

$context = Playwright::chromium(['headless' => true]);
$context->newPage();

// Announce readiness only once the browser is actually up, so the test never
// races against a half-started process tree.
file_put_contents($readyFile, (string) getmypid());

sleep(300);
