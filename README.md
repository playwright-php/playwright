<div align="center">
<img src="https://github.com/playwright-php/.github/raw/main/profile/playwright-php.png" alt="Playwright PHP" />

&nbsp; ![PHP Version](https://img.shields.io/badge/PHP-8.3+-05971B?labelColor=09161E&color=1D8D23&logoColor=FFFFFF)
&nbsp; ![CI](https://img.shields.io/github/actions/workflow/status/playwright-php/playwright/CI.yaml?branch=main&label=Tests&color=1D8D23&labelColor=09161E&logoColor=FFFFFF)
&nbsp; ![Release](https://img.shields.io/github/v/release/playwright-php/playwright?label=Stable&labelColor=09161E&color=1D8D23&logoColor=FFFFFF)
&nbsp; ![License](https://img.shields.io/github/license/playwright-php/playwright?label=License&labelColor=09161E&color=1D8D23&logoColor=FFFFFF)

</div>

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

```
composer require playwright-php/playwright
```

Optionnally, install the Playwright browsers:

```
bin/playwright-install
```

This installs the Node server dependencies under `bin/` and fetches Playwright browsers.

## Quick Start

```php
<?php

require __DIR__.'/vendor/autoload.php';

use PlaywrightPHP\Playwright;

// Launch Chromium and get a context
$context = Playwright::chromium(['headless' => true]);

// Create a new page and navigate to a website
$page = $context->newPage();
$page->goto('https://example.com');

// Print the page title
echo $page->title().PHP_EOL; // "Example Domain"

$context->close();
```

Tip: Debug with Inspector by running headed and pausing:
- Env: `PWDEBUG=1 php your_script.php`
- Builder: `$playwright->chromium()->withHeadless(false)->withInspector()->launch();` then `$page->pause();`

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

## Debugging with Playwright Inspector

- Enable Inspector via builder: call `withInspector()` and run headed.
  - Example: `$browser = $playwright->chromium()->withHeadless(false)->withInspector()->launch();`
- Pause at a point to open Inspector: `$page->pause();`
- Alternatively, export `PWDEBUG` when running your script to enable Inspector globally:
  - macOS/Linux: `PWDEBUG=1 php your_script.php`
  - Windows (PowerShell): `$env:PWDEBUG='1'; php your_script.php`

Notes:
- Inspector opens from the Node server; `PWDEBUG` is forwarded automatically.
- A headed browser (`headless: false`) makes it easier to see UI interactions while debugging.
