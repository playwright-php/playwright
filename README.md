# Playwright for PHP

Modern, PHP-native browser automation on top of Microsoft Playwright.

## Highlights

- Powerful: Drive Chromium, Firefox, and WebKit with one API
- Reliable: Auto-waits and locator model reduce flakiness
- Expressive: Jest-style `expect()` assertions for pages and locators
- Test-ready: PHPUnit integration and convenient test helpers

## Requirements

- PHP 8.3+
- Node.js 20+ (used by the Playwright server and browser binaries)

## Install

Project dependency:
- composer require playwright-php/playwright

Local/dev setup for this repo:
- composer install
- composer run install-browsers

This installs the Node server dependencies under `bin/` and fetches Playwright browsers.

## Quick Start

```php
<?php

require __DIR__.'/vendor/autoload.php';

use PlaywrightPHP\Playwright;

// Launch Chromium and get a context
$context = Playwright::chromium(['headless' => true]);
$page = $context->newPage();
$page->goto('https://example.com');

echo $page->title().PHP_EOL; // "Example Domain"

$context->close();
```

Minimal `expect()` example:

```php
<?php
require __DIR__.'/vendor/autoload.php';

use PlaywrightPHP\Playwright;
use function PlaywrightPHP\Testing\expect;

$context = Playwright::chromium();
$page = $context->newPage();
$page->goto('https://example.com');

expect($page->locator('h1'))->toHaveText('Example Domain');
expect($page->locator('h1 ~ p'))->toHaveCount(2);

$context->close();
```

You can also run the ready-made example:
- php docs/examples/example_expect.php

## Scripts

- composer test — runs the full test suite
- composer analyse — static analysis (PHPStan)
- composer cs-check — code style check
- composer cs-fix — code style fix
- composer run install-browsers — installs server deps and browsers (in `bin/`)

## Notes on the Server

- The Node-based Playwright server and its dependencies live under `bin/`.
- Composer’s `install-browsers` script installs dependencies in `bin/` and runs `npx playwright install` there.
- The PHP transport auto-starts the Node server as needed; no manual server process is required.

