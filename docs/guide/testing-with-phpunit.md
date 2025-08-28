# Testing with PHPUnit

While Playwright for PHP can be used as a standalone automation library, its real power shines when integrated into a
testing workflow. This library provides a first-class integration with PHPUnit, the de-facto standard for testing in the
PHP ecosystem.

This integration is designed to be seamless, handling all the boilerplate setup and teardown for you, so you can focus
on writing your test logic.

## Setup

The easiest way to get started is by extending the `PlaywrightPHP\Testing\PlaywrightTestCase` class in your test files.
This base class automatically manages the entire lifecycle of the browser and page for you.

```php
<?php

namespace App\Tests;

use PlaywrightPHP\Testing\PlaywrightTestCase;
use function PlaywrightPHP\Testing\expect;

class MyWebAppTest extends PlaywrightTestCase
{
    // Your tests go here
}
```

By extending `PlaywrightTestCase`, you gain access to several useful properties and methods within your tests without
any extra configuration.

## Writing a Test

Once your test class is set up, you can immediately start writing tests using the pre-configured properties provided by
the base class:

* `$this->page`: A new, clean `Page` object for each test.
* `$this->context`: The `BrowserContext` for the page.
* `$this->browser`: The `Browser` instance.

Here is a complete example of a test that verifies a login form.

```php
<?php

namespace App\Tests;

use PlaywrightPHP\Testing\PlaywrightTestCase;
use function PlaywrightPHP\Testing\expect;

class MyWebAppTest extends PlaywrightTestCase
{
    public function testUserCanLogInSuccessfully(): void
    {
        // 1. Navigate to the login page
        $this->page->goto('https://my-app.com/login');

        // 2. Fill in the form
        $this->page->locator('#username')->fill('my-user');
        $this->page->locator('#password')->fill('secure-password');
        $this->page->locator('button[type="submit"]')->click();

        // 3. Assert that the login was successful
        expect($this->page)->toHaveURL('https://my-app.com/dashboard');
        expect($this->page->locator('h1'))->toHaveText('Welcome, my-user!');
    }
}
```

## Assertions with `expect()`

To make your tests more readable and expressive, the library includes a fluent assertion helper, `expect()`. This
provides a set of domain-specific assertions that automatically wait for conditions to be met.

```php
// Assert that an element is visible on the page.
expect($this->page->locator('.success-message'))->toBeVisible();

// Assert that the page has a specific title.
expect($this->page)->toHaveTitle('My App Dashboard');

// You can negate any assertion with the ->not() modifier.
expect($this->page->locator('.error-message'))->not()->toBeVisible();
```

For a full list of available assertions, please see the *
*[Assertions Reference](https://www.google.com/search?q=./assertions-reference.md)**.

## Debugging Failures

When a test fails, the library automatically provides tools to help you debug:

* **Screenshots:** A screenshot of the page at the moment of failure is automatically saved to a `test-failures`
  directory in your project root.
* **Tracing:** You can enable tracing by setting the `PW_TRACE` environment variable. When a test fails with tracing
  enabled, a `trace.zip` file is generated. You can drag and drop this file into
  the [Playwright Trace Viewer](https://playwright.dev/docs/trace-viewer) to see a complete, time-traveling recording of
  your test's execution.
