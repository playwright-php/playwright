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

require_once __DIR__.'/../../vendor/autoload.php';

use Playwright\Playwright;

$browser = Playwright::chromium();
$page = $browser->newPage();

$page->goto('https://example.com');
echo 'Title: '.$page->title();

$page->close();
$browser->close();
