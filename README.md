<div align="center">
<img src="https://github.com/playwright-php/.github/raw/main/profile/playwright-php.png" alt="Playwright PHP" />

&nbsp; ![PHP Version](https://img.shields.io/badge/PHP-8.3+-05971B?labelColor=09161E&color=1D8D23&logoColor=FFFFFF)
&nbsp; ![CI](https://img.shields.io/github/actions/workflow/status/playwright-php/playwright/CI.yaml?branch=main&label=Tests&color=1D8D23&labelColor=09161E&logoColor=FFFFFF)
&nbsp; ![Release](https://img.shields.io/github/v/release/playwright-php/playwright?label=Stable&labelColor=09161E&color=1D8D23&logoColor=FFFFFF)
&nbsp; ![License](https://img.shields.io/github/license/playwright-php/playwright?label=License&labelColor=09161E&color=1D8D23&logoColor=FFFFFF)

</div>

# Playwright for PHP

Modern, PHP‑native browser automation powered by Microsoft Playwright.

## About

Playwright for PHP lets you launch real browsers (Chromium, Firefox, WebKit), drive pages and locators, and write reliable end‑to‑end tests — all from PHP.

- Familiar model: browser → context → page → locator
- Auto‑waiting interactions reduce flakiness
- PHPUnit integration with a base test case and fluent `expect()` assertions
- Cross‑browser: Chromium, Firefox, and WebKit supported

Requirements:
- PHP 8.3+
- Node.js 20+ (used by the bundled Playwright server and browsers)

## Install

Add the library to your project:

```
composer require playwright-php/playwright
```

Install the Playwright browsers (Chromium, Firefox, WebKit):

```
composer run install-browsers
# or, if your environment needs extra OS deps
composer run install-browsers-with-deps
```

The PHP library installs and manages a lightweight Node server under the hood; no manual server process is required.

## Usage

### Quick start

Open a page and print its title:

```php
<?php

require __DIR__.'/vendor/autoload.php';

use PlaywrightPHP\Playwright;

$context = Playwright::chromium(['headless' => true]);
$page = $context->newPage();
$page->goto('https://example.com');

echo $page->title().PHP_EOL; // Example Domain

$context->close();
```

### Browser

- Choose a browser: `Playwright::chromium()`, `Playwright::firefox()`, or `Playwright::webkit()`.
- `Playwright::safari()` is an alias of `webkit()`.
- Common launch options: `headless` (bool), `slowMo` (ms), `args` (array of CLI args), and an optional `context` array.

```php
$context = Playwright::webkit([
    'headless' => false,
    'slowMo' => 200,
    'args' => ['--no-sandbox'],
]);
```

### Page

Create pages, navigate, evaluate scripts, and take screenshots:

```php
$page = $context->newPage();
$page->goto('https://example.com');

$html = $page->content();
$title = $page->title();
$path = $page->screenshot(__DIR__.'/screenshot.png');

$answer = $page->evaluate('() => 6 * 7'); // 42
```

### Locator

Work with auto‑waiting locators for reliable interactions and assertions:

```php
use function PlaywrightPHP\Testing\expect;

$h1 = $page->locator('h1');
expect($h1)->toHaveText('Example Domain');

$search = $page->locator('#q');
$search->fill('playwright php');
$page->locator('form')->submit();

// Compose and filter
$items = $page->locator('.result-item');
expect($items)->toHaveCount(10);
```

### Server

- A lightweight Node.js Playwright server is installed under `bin/` and started automatically by the PHP library.
- Install browsers with: `composer run install-browsers` (or `install-browsers-with-deps`).
- Requires Node.js 20+ in your environment (local and CI).

### Inspector (debugging)

- Run headed by setting `headless => false`.
- Export `PWDEBUG=1` to open the Inspector: `PWDEBUG=1 php your_script.php`.
- You can also call `$page->pause()` to break into the Inspector during a run.

More examples: see `docs/examples/` (e.g., `php docs/examples/expect.php`).

### Route interception

Intercept and control network requests at page or context scope.

- Page-level block (e.g., images):

```php
$page->route('**/*.png', function ($route) {
    $route->abort(); // block images
});
```

- Context-level stub API and pass-through others:

```php
$context->route('**/api/todos', function ($route) {
    $route->fulfill([
        'status' => 200,
        'contentType' => 'application/json',
        'body' => json_encode(['items' => []]),
    ]);
});

$context->route('*', fn ($route) => $route->continue());
```

See runnable examples:
- `php docs/examples/route_block_images.php`
- `php docs/examples/route_stub_api.php`

## Testing

Integrate with PHPUnit using the provided base class:

```php
<?php

use PlaywrightPHP\Testing\PlaywrightTestCase;
use function PlaywrightPHP\Testing\expect;

final class MyE2ETest extends PlaywrightTestCase
{
    public function test_homepage(): void
    {
        $this->page->goto('https://example.com');
        expect($this->page->locator('h1'))->toHaveText('Example Domain');
    }
}
```

Tips:
- On failure, screenshots are saved under `test-failures/`.
- Enable tracing with `PW_TRACE=1` to capture a `trace.zip` for Playwright Trace Viewer.
- Guides: `docs/guide/getting-started.md`, `docs/guide/testing-with-phpunit.md`, `docs/guide/assertions-reference.md`.

## Contributing

Contributions are welcome. Please use Conventional Commits, include tests for behavior changes, and ensure docs/examples are updated when relevant. See `docs/contributing/testing.md` for local workflow.

## Licence

MIT — see `LICENSE` for details.
