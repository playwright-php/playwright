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

// Start a reusable Playwright browser server for external clients.
// This is useful when you want one long-lived browser shared by many scripts.

$playwright = PlaywrightFactory::create();

echo "Launching browser server...\n";

$server = $playwright->launchServer('chromium', [
    'headless' => true,
]);

$wsEndpoint = $server->wsEndpoint();

echo "Browser server running!\n";
echo "WebSocket endpoint: {$wsEndpoint}\n";
echo "\nOther processes can connect using:\n";
echo "\$browser = \$playwright->chromium()->connect('{$wsEndpoint}');\n";
echo "\nPress Ctrl+C to stop the server...\n";

// Keep serving until interrupted so clients can keep connecting to this endpoint.
while (true) {
    sleep(1);
}
