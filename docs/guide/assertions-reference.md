# Assertions Reference

Playwright for PHP includes a powerful assertion library accessible via the `expect()` function. This provides a fluent
and expressive way to write checks in your tests.

A key feature of these assertions is **auto-waiting**. When you write an assertion, Playwright will automatically wait
for a reasonable amount of time for the condition to be met. This eliminates a major source of flaky tests.

## Using `expect()`

The `expect()` function can be passed either a `Locator` or a `Page` object. It returns an assertion object that you can
use to make claims about the state of your application.

```php
use function Playwright\Testing\expect;

// Make an assertion about a locator
expect($this->page->locator('h1'))->toHaveText('Welcome!');

// Make an assertion about the page
expect($this->page)->toHaveURL('https://my-app.com/dashboard');
```

-----

## Modifiers

You can alter the behavior of any assertion using these chainable modifier methods.

### `.not()`

The `.not()` modifier negates any assertion that follows it.

```php
// Assert that an element is not visible
expect($this->page->locator('.loading-spinner'))->not()->toBeVisible();

// Assert that a checkbox is not checked
expect($this->page->locator('#terms-and-conditions'))->not()->toBeChecked();
```

### `.withTimeout()`

By default, assertions have a timeout of 5 seconds. You can override this for a specific assertion using
`withTimeout()`.

```php
// Wait up to 10 seconds for the success message to appear
expect($this->page->locator('.success-message'))
    ->withTimeout(10000)
    ->toBeVisible();
```

-----

## Locator Assertions

These assertions are available when you pass a `Locator` to `expect()`.

* **`toBeVisible()`**: Asserts the locator resolves to a visible element.
* **`toBeHidden()`**: Asserts the locator resolves to a hidden element.
* **`toBeEnabled()`**: Asserts the element is enabled.
* **`toBeDisabled()`**: Asserts the element is disabled.
* **`toBeChecked()`**: Asserts a checkbox or radio button is checked.
* **`toBeFocused()`**: Asserts the element is focused.
* **`toHaveText(string $text)`**: Asserts the element contains the given text.
* **`toHaveExactText(string $text)`**: Asserts the element's text is an exact match.
* **`toContainText(string $text)`**: An alias for `toHaveText()`.
* **`toHaveValue(string $value)`**: Asserts an input element has a specific value.
* **`toHaveAttribute(string $name, string $value)`**: Asserts the element has the given attribute and value.
* **`toHaveCSS(string $name, string $value)`**: Asserts the element has the given computed CSS style.
* **`toHaveCount(int $count)`**: Asserts the locator resolves to a specific number of elements.

-----

## Page Assertions

These assertions are available when you pass a `Page` object to `expect()`.

* **`toHaveURL(string $url)`**: Asserts the page's current URL is a match.
* **`toHaveTitle(string $title)`**: Asserts the page's title is a match.
