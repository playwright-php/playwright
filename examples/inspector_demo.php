<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PlaywrightPHP\PlaywrightFactory;
use PlaywrightPHP\Configuration\PlaywrightConfig;

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
