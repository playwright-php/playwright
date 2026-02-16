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

use Playwright\PlaywrightFactory;

// Connect to an external Playwright browser-server instance started elsewhere.
// This expects a Playwright WebSocket endpoint, not a Chrome DevTools (CDP) URL.
// launchServer() starts a browser process and exposes wsEndpoint() for connect().

$playwright = PlaywrightFactory::create();

// Provide the wsEndpoint() emitted by a process that called launchServer().
$remoteWsEndpoint = getenv('REMOTE_WS_ENDPOINT');

if (false === $remoteWsEndpoint || '' === $remoteWsEndpoint) {
    echo "REMOTE_WS_ENDPOINT is not set.\n";
    echo "This example needs the exact wsEndpoint() value from launchServer().\n";
    echo "\nQuick setup:\n";
    echo "1. In another PHP process, run:\n";
    echo "   \$server = \$playwright->launchServer('chromium', ['headless' => true]);\n";
    echo "   echo \$server->wsEndpoint();\n";
    echo "   while (true) { sleep(1); }\n";
    echo "2. Run this example with:\n";
    echo "   REMOTE_WS_ENDPOINT='ws://127.0.0.1:PORT/ID' php docs/examples/connect_remote_browser.php\n";
    $playwright->close();

    exit(1);
}

echo "Attempting to connect to remote browser at: {$remoteWsEndpoint}\n";

try {
    $browser = $playwright->chromium()->connect($remoteWsEndpoint);
    echo "Successfully connected to remote browser!\n";

    $context = $browser->newContext();
    $page = $context->newPage();

    $page->goto('https://example.com');
    echo "Page title: {$page->title()}\n";
    echo "URL: {$page->url()}\n";

    // Keep remote servers alive for other clients; only close this client connection.
    $browser->close();
    echo "Browser connection closed.\n";
} catch (Exception $e) {
    echo "Failed to connect: {$e->getMessage()}\n";
    echo "Tip: REMOTE_WS_ENDPOINT must be the full wsEndpoint() value, including path.\n";
    echo "\nTo set up an external Playwright browser-server instance:\n";
    echo "1. Start any PHP process with:\n";
    echo "   \$server = \$playwright->launchServer('chromium', ['headless' => true]);\n";
    echo "   echo \$server->wsEndpoint();\n";
    echo "   while (true) { sleep(1); }\n";
    echo "2. Copy its wsEndpoint() value (ws://...)\n";
    echo "3. Set REMOTE_WS_ENDPOINT to that value and run this script again\n";
}

$playwright->close();
