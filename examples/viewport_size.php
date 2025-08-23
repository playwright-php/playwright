<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';

use PlaywrightPHP\Playwright;

// Headless can be set to false to see the browser
$browser = Playwright::chromium(['headless' => true]);

$page = $browser->newPage(['viewport' => ['width' => 1200, 'height' => 800]]);

$page->goto('https://howbigismybrowser.com/');

$viewport = $page->viewportSize();
echo sprintf('Current viewport: %dx%d', $viewport['width'], $viewport['height'])."\n";

$screenshot = $page->screenshot('./viewport_1200x800.png');
echo 'Screenshot saved to: '.$screenshot."\n";

// Change viewport size at runtime
$page->setViewportSize(1920, 1080);
$page->reload();

$viewport = $page->viewportSize();
echo sprintf('New viewport: %dx%d', $viewport['width'], $viewport['height'])."\n";

$screenshot = $page->screenshot('./viewport_1920x1080.png');
echo 'Screenshot saved to: '.$screenshot."\n";

$page->close();
$browser->close();
