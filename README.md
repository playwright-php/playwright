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
- PHPUnit integration with a base trait and fluent `expect()` assertions
- Cross‑browser: Chromium, Firefox, and WebKit supported
- No separate server to manage — a lightweight Node server is started for you

Requirements:
- PHP 8.3+
- Node.js 20+ (used by the bundled Playwright server and browsers)

## Install

Add the library to your project:

```bash
composer require playwright-php/playwright
```

Install the Playwright browsers (Chromium, Firefox, WebKit):

```bash
# Always run this from your app after composer install
vendor/bin/playwright-install

# If your environment needs extra OS dependencies, add:
vendor/bin/playwright-install --with-deps
```


## Quick start

Open a page and print its title:

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Playwright\Playwright;

$context = Playwright::chromium(['headless' => true]);
$page = $context->newPage();
$page->goto('https://example.com');

echo $page->title().PHP_EOL; // Example Domain

$context->close();
```

## Usage

### Browser

- Choose a browser: `Playwright::chromium()`, `Playwright::firefox()`, or `Playwright::webkit()`.
- `Playwright::safari()` is an alias of `webkit()`.
- Common launch options: `headless` (bool), `slowMo` (ms), `args` (array of CLI args), and an optional `context` array with context options.

```php
$context = Playwright::webkit([
    'headless' => false,
    'slowMo'   => 200,
    'args'     => ['--no-sandbox'],
    // 'context' => [ ... context options ... ],
]);
```

### Page

Create pages, navigate, evaluate scripts, and take screenshots:

```php
$page = $context->newPage();
$page->goto('https://example.com');

$html  = $page->content();
$title = $page->title();

$path = $page->screenshot(__DIR__.'/screenshot.png');
```

### Locators and interactions

```php
$button = $page->locator('text=Sign in');
$button->click();

$username = $page->locator('#username');
$username->fill('alice@example.com');

$password = $page->locator('#password');
$password->fill('s3cret');
$password->press('Enter');
```

### Storage state and context reuse

```php
$context->storageState(__DIR__.'/state.json');

// Later in another process
$ctx = Playwright::chromium([
  'context' => ['storageState' => __DIR__.'/state.json'],
]);
```

## PHPUnit integration

The package provides a testing trait and fluent `expect()` assertions to write robust E2E tests.

Minimal example:

```php
<?php

use PHPUnit\Framework\TestCase;
use Playwright\Testing\PlaywrightTestCaseTrait;

final class HomePageTest extends TestCase
{
    use PlaywrightTestCaseTrait;

    public function test_title_is_correct(): void
    {
        $context = $this->playwright()->chromium(['headless' => true]);
        $page    = $context->newPage();

        $page->goto('https://example.com');

        $this->expect()->toBe($page->title(), 'Example Domain');

        $context->close();
    }
}
```

Notes:
- The trait bootstraps and tears down Playwright for each test class.
- If you prefer full control, you can skip the trait and use the static `Playwright` facade directly.

## CI usage (GitHub Actions)

Example workflow snippet:

```yaml
jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - uses: actions/setup-node@v4
        with:
          node-version: '20'

      - run: composer install --no-interaction --prefer-dist

      # Install browsers for Playwright PHP
      - run: vendor/bin/playwright-install --with-deps

      - run: vendor/bin/phpunit --colors=always
```

Tips:
- Cache Node and Composer if you need faster builds.
- You can also cache Playwright browsers under `~/.cache/ms-playwright`.

## Contributing

Contributions are welcome. Please use Conventional Commits, include tests for behavior changes, and ensure docs/examples are updated when relevant. See `docs/contributing/testing.md` for local workflow.

## License

This package is released by the [Playwright PHP](https://playwright-php.dev) 
project under the MIT License. See the [LICENSE](LICENSE) file for details.
