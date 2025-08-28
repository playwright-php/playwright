# Core Concepts

Playwright's power and reliability come from its well-defined architecture. Understanding these core concepts is key to
using the library effectively and writing robust browser automation scripts. The API is structured around four main
objects: `Browser`, `BrowserContext`, `Page`, and `Locator`.

## Browser

A `Browser` instance represents a single browser process (e.g., Chromium, Firefox, or WebKit). You typically launch a
browser once at the beginning of your script or test suite and close it at the end.

While you can interact with the `Browser` object directly, most of your work will be done within a `BrowserContext`.

```php
<?php
use PlaywrightPHP\Playwright;

// The Playwright static class is the easiest way to get started.
// This launches a Chromium browser and returns a default BrowserContext.
$context = Playwright::chromium(['headless' => false]);

// ... do stuff ...

// Closing the context will also close the associated browser.
$context->close();
```

## BrowserContext

A `BrowserContext` is an isolated, "incognito-like" session within a browser instance. Each context has its own cookies,
local storage, and cache, and they do not share these with other contexts. This makes them perfect for running
independent tests in parallel.

You can create multiple contexts from a single `Browser` instance.

```php
// The Browser object is available on the context.
$browser = $context->browser();

// Create a second, isolated context.
$adminContext = $browser->newContext();

// Create a new page in each context.
$userPage = $context->newPage();
$adminPage = $adminContext->newPage();
```

Contexts are also where you configure session-specific behavior, such as:

* Setting a custom viewport size.
* Emulating mobile devices.
* Granting permissions.
* Loading a saved authentication state.

## Page

A `Page` represents a single tab within a `BrowserContext`. It is the primary object you will use to interact with a web
page's content. Most of the essential actions, like navigating, clicking, and typing, are methods on the `Page` object.

```php
// Navigate the page to a URL.
$page->goto('https://github.com');

// Interact with elements on the page.
$page->click('a.HeaderMenu-link--sign-in');
$page->type('#login_field', 'my-username');
```

A `BrowserContext` can have multiple `Page` objects (tabs).

## Locator

The `Locator` is the heart of Playwright's modern approach to browser automation. It is the recommended way to find and
interact with elements on a `Page`.

A `Locator` is a "recipe" for finding an element on the page. Unlike traditional methods that find an element
immediately, a `Locator` has **auto-waiting** built-in. When you perform an action on a locator, Playwright
automatically waits for the element to exist and be in an "actionable" state (e.g., visible, enabled, not obscured)
before performing the action. This eliminates a major source of flakiness in browser tests.

```php
// Create a locator for the search input field.
$searchInput = $page->locator('#search-input');

// Playwright will automatically wait for the element to be ready
// before filling it with text.
$searchInput->fill('Playwright for PHP');

// You can chain locators to find elements within other elements.
$header = $page->locator('header');
$signInButton = $header->locator('a:has-text("Sign In")');
$signInButton->click();
```

By understanding and using these four core concepts, you can build powerful, reliable, and maintainable browser
automation scripts and tests.
