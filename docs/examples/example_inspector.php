<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require __DIR__.'/../../vendor/autoload.php';

use PlaywrightPHP\Configuration\PlaywrightConfig;
use PlaywrightPHP\PlaywrightFactory;

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
