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

use Playwright\Playwright;

use function Playwright\Testing\expect;

$context = Playwright::chromium();
$page = $context->newPage();
$page->goto('https://example.com');

expect($page->locator('h1'))->toHaveText('Example Domain');

$paragraphs = $page->locator('h1 ~ p');

expect($paragraphs->first())->toContainText('illustrative examples');
expect($paragraphs->locator('a'))->toHaveAttribute('href', 'https://www.iana.org/domains/example');

$context->close();
