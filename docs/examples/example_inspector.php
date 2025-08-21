<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use PlaywrightPHP\PlaywrightFactory;
use PlaywrightPHP\Configuration\PlaywrightConfig;

// Launch headed with Inspector enabled, then pause.

$config = new PlaywrightConfig(headless: false);
$playwright = PlaywrightFactory::create($config);

$browser = $playwright->chromium()->withInspector()->launch();
$page = $browser->newPage();
$page->goto('https://example.com');

echo "Opening Inspector via page->pause()...\n";
$page->pause();

echo "Resuming and closing...\n";
$browser->close();
