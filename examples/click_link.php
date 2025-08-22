<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';

use PlaywrightPHP\Playwright;
use PlaywrightPHP\PlaywrightFactory;

$client = PlaywrightFactory::create();
$browser = $client->chromium()->launch();
$page = $browser->newPage();

$page->goto('https://example.com');
echo 'URL: '.$page->url()."\n";

$page->locator('a')->click();
echo 'New URL: '.$page->url()."\n";

$page->locator('a')->last()->click();
echo 'New URL: '.$page->url()."\n";

$browser->close();
$client->close();
