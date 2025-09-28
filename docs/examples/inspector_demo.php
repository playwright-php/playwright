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

use Playwright\Configuration\PlaywrightConfig;
use Playwright\PlaywrightFactory;

// Simple demo: launch headed Chromium with Inspector and pause.

$config = new PlaywrightConfig(
    headless: false,
);

$playwright = PlaywrightFactory::create($config);
$browser = $playwright->chromium()->withInspector()->launch();

$page = $browser->newPage();
$page->goto('https://example.com');

// This opens Playwright Inspector and pauses execution.
echo "Calling page->pause() — Inspector should open now...\n";
$page->pause();

echo "Resumed from Inspector. Closing...\n";
$browser->close();
