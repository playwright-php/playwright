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

$playwright = PlaywrightFactory::create();

// 1. Launch the browser as usual
$browser = $playwright->chromium()->launch();

// 2. Create a new context with specific viewport and user agent options
$context = $browser->newContext([
    'viewport' => [
        'width' => 1920,
        'height' => 1080,
    ],
    'deviceScaleFactor' => 2,
    'locale' => 'en-US',
]);

// 3. Create a new page *from that context*
$page = $context->newPage();

// 4. All actions on this page will now use the context's settings
$page->goto('https://www.whatismybrowser.com/');
$page->screenshot('browser-config-test.png');

$page->goto('https://www.apple.com/fr');
$page->screenshot('apple.com-fr.png');

$browser->close();
$playwright->close();
