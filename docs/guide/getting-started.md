# Getting Started with Playwright for PHP

Welcome to Playwright for PHP\! This guide will walk you through setting up your first project and running a basic
browser automation script. Our goal is to get you up and running in just a few minutes.

## Requirements

Before you begin, please ensure your development environment meets the following requirements:

* **PHP 8.3+**
* **Node.js 20+** (This is used by the underlying Playwright server)
* **Composer** for managing PHP dependencies.

## Installation

Getting started with Playwright for PHP is a two-step process. First, you add the library to your project using
Composer. Second, you run a command to download the necessary browser binaries.

### Step 1: Install the Library

Navigate to your project's root directory and run the following Composer command:

```bash
composer require playwright-php/playwright
```

### Step 2: Install Browsers

Playwright needs to download browser binaries (for Chromium, Firefox, and WebKit) to work. The library ships with a PHP
installer that works the same for applications and for this repository. Run the following command from your project's
root:

```bash
vendor/bin/playwright-install --browsers
```

Need Playwright to pull in recommended system dependencies as well (handy on fresh CI runners)?

```bash
vendor/bin/playwright-install --with-deps
```

Both commands ensure the bundled Playwright server is up to date and then download the latest browser versions into the
local Playwright cache.

## Your First Script

You're now ready to write your first script. Create a new file named `example.php` and add the following code:

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Playwright\Playwright;

// Start a new Playwright client and launch a browser.
// By default, it launches a headless Chromium instance.
$context = Playwright::chromium();

// Create a new page within the browser context.
$page = $context->newPage();

// Navigate to a website.
$page->goto('https://example.com');

// Get the title of the page and print it to the console.
echo $page->title() . PHP_EOL; // Outputs: "Example Domain"

// Take a screenshot and save it as 'screenshot.png'.
$page->screenshot('screenshot.png');

// Export the page to PDF on disk.
$page->pdf('invoice.pdf', ['format' => 'A4']);

// Or grab the PDF bytes directly without keeping files around.
$pdfBytes = $page->pdfContent();
file_put_contents('inline-invoice.pdf', $pdfBytes);

// Close the browser context and all its pages.
$context->close();
```

To run the script, execute it from your terminal:

```bash
php example.php
```

You should see "Example Domain" printed to your console, and a `screenshot.png` file will be created in the same
directory.

Congratulations, you've successfully run your first Playwright for PHP script\!

## What's Next?

Now that you have the library installed and running, you can explore its core concepts to understand how to build more
complex and reliable automations.

* **[Core Concepts](./core-concepts.md)**: Learn about the fundamental building blocks
  of Playwright like Browsers, Contexts, Pages, and Locators.
* **[Testing with PHPUnit](./testing-with-phpunit.md)**: See how to integrate Playwright
  into your PHPUnit test suite for seamless end-to-end testing.
