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

$context = Playwright::chromium([
    'headless' => true,
]);
$page = $context->newPage();

// Block PNG images on this page
$page->route('**/*.png', function ($route): void {
    $route->abort();
});

$page->goto('https://example.com');
echo 'Loaded example.com with images blocked'.PHP_EOL;

$context->close();
