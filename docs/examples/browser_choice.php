<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use PlaywrightPHP\Browser\BrowserInterface;
use PlaywrightPHP\PlaywrightFactory;

$playwright = PlaywrightFactory::create();
$browsers = [
    'chromium' => $playwright->chromium(...),
    'firefox' => $playwright->firefox(...),
    'webkit' => $playwright->webkit(...),
];

foreach ($browsers as $type => $browserBuilder) {
    $browser = ($builder = $browserBuilder())->launch(['headless' => true]);
    assert($browser instanceof BrowserInterface);

    $page = $browser->newPage();
    $page->goto('https://www.whatismybrowser.com/');
    $page->locator('text=Your Browser is:')->waitFor();
    echo sprintf('Browser: %s %s | URL: %s', $type, $browser->version(), $page->url())."\n";

    $page->close();
    $browser->close();
}
