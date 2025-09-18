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

Playwright needs to download browser binaries (for Chromium, Firefox, and WebKit) to work. The library provides a
convenient script to handle this for you. Run the following command from your project's root:

```bash
composer run install-browsers
```

This will install the Node.js dependencies for the server and download the latest browser versions into a local cache.

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
