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

if (!is_string($uri)) {
    $uri = '/';
}

if ('/api/echo' === $uri) {
    $body = file_get_contents('php://input');
    if (!is_string($body)) {
        $body = '';
    }

    $json = null;
    if ('' !== $body) {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $json = $decoded;
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'body' => $body,
        'json' => $json,
        'query' => $_GET,
        'cookies' => $_COOKIE,
        'requestHeader' => $_SERVER['HTTP_X_PLAYWRIGHT_PHP'] ?? null,
    ], \JSON_THROW_ON_ERROR);

    return true;
}

if ('/api/set-cookie' === $uri) {
    header('Content-Type: application/json');
    header('Set-Cookie: api-session=from-api; Path=/; SameSite=Lax');
    echo '{"cookie":"set"}';

    return true;
}

if (1 === preg_match('#^/api/status/(\d{3})$#', $uri, $matches)) {
    $status = (int) $matches[1];
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['status' => $status], \JSON_THROW_ON_ERROR);

    return true;
}

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
