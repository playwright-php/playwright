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

$context = Playwright::chromium(['headless' => true]);
$page = $context->newPage();

// Stub an API endpoint at the context level
$context->route('**/api/todos', function ($route): void {
    $route->fulfill([
        'status' => 200,
        'contentType' => 'application/json',
        'body' => json_encode(['items' => [
            ['id' => 1, 'title' => 'stubbed'],
        ]]),
    ]);
});

$page->goto('https://example.com');

// Trigger the stubbed endpoint and print the result length
$count = $page->evaluate(<<<'JS'
    async () => {
        const res = await fetch('/api/todos');
        const json = await res.json();
        return Array.isArray(json.items) ? json.items.length : 0;
    }
JS);

echo "Stubbed items count: {$count}".PHP_EOL;

$context->close();
