<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require_once __DIR__.'/../../vendor/autoload.php';

use PlaywrightPHP\Playwright;

$browser = Playwright::chromium();
$page = $browser->newPage();

$page->goto('https://example.com');
echo 'Title: '.$page->title();

$page->close();
$browser->close();
