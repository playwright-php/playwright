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

require __DIR__.'/../../vendor/autoload.php';

use Playwright\Exception\PlaywrightException;
use Playwright\PlaywrightFactory;

$playwright = PlaywrightFactory::create();

// Example 1: launchServer() exposes a Playwright WebSocket endpoint that can be reused
// by other processes. This endpoint is ephemeral and valid while the server is running.
echo "=== Example 1: Launch Browser Server ===\n";
$server = $playwright->launchServer('chromium', ['headless' => true]);
$wsEndpoint = $server->wsEndpoint();
echo "Browser server started at: {$wsEndpoint}\n";

// Example 2: connect() uses Playwright protocol, so it must target a launchServer() endpoint.
echo "\n=== Example 2: Connect to Browser Server ===\n";
$browser = $playwright->chromium()->connect($wsEndpoint);
$context = $browser->newContext();
$page = $context->newPage();
$page->goto('https://example.com');
echo "Page title: {$page->title()}\n";

$browser->close();
// Closing the client connection does not stop the server process.
$server->close();

// Example 3: connectOverCDP() is Chromium-only and attaches to a running Chrome instance.
// First, start Chrome with remote debugging enabled.
echo "\n=== Example 3: Connect Over CDP (Chromium only) ===\n";
echo "To use this example:\n";
echo "1. Start Chrome with remote debugging on port 9222\n";
echo "   macOS example:\n";
echo "   \"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome\" --remote-debugging-port=9222 --user-data-dir=/tmp/chrome-pw\n";
echo "2. Optionally set CDP_ENDPOINT (default: http://127.0.0.1:9222)\n";

$cdpEndpoint = getenv('CDP_ENDPOINT') ?: 'http://127.0.0.1:9222';

try {
    $cdpBrowser = $playwright->chromium()->connectOverCDP($cdpEndpoint);
    $cdpContext = $cdpBrowser->newContext();
    $cdpPage = $cdpContext->newPage();
    $cdpPage->goto('https://playwright.dev');
    echo "Connected via CDP! Title: {$cdpPage->title()}\n";
    $cdpBrowser->close();
} catch (PlaywrightException $e) {
    echo "CDP connection failed at {$cdpEndpoint}: {$e->getMessage()}\n";
}

$playwright->close();

echo "\nDone!\n";
