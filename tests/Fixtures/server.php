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

// Serves files from the html/ subdirectory

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Handle root path explicitly
if ('/' === $requestUri) {
    $requestUri = '/index.html';
}

// Map requests to the html/ subdirectory
$filePath = __DIR__.'/html'.$requestUri;

if (file_exists($filePath) && is_file($filePath)) {
    // Determine content type based on extension
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $contentTypes = [
        'html' => 'text/html',
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
    readfile($filePath);

    return true;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><body><h1>404 Not Found</h1></body></html>';

return true;
