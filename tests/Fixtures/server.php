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

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', \PHP_URL_PATH);

// Default to index.html if root
if ('/' === $uri) {
    $uri = '/index.html';
}

// Construct file path
$file = __DIR__.'/html'.$uri;

// Serve static file if it exists
if (file_exists($file) && is_file($file)) {
    // Determine content type
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $contentTypes = [
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];
    
    $contentType = $contentTypes[$extension] ?? 'application/octet-stream';
    header('Content-Type: '.$contentType);
    readfile($file);
    return true;
}

// 404 for missing files
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html>';
echo '<html><head><meta charset="utf-8"><title>404 Not Found</title></head>';
echo '<body><h1>404 Not Found</h1><p>File not found: '.htmlspecialchars($uri, \ENT_QUOTES, 'UTF-8').'</p></body>';
echo '</html>';
return true;
