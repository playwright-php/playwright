# Advanced Recipes

This page contains recipes for common but more advanced automation scenarios. Use these examples to leverage the full
power of Playwright for PHP.

## File Uploads

You can upload one or more files to an `<input type="file">` element using the `setInputFiles()` method on a `Locator`.

```php
// Find the file input element
$fileChooser = $this->page->locator('#upload-input');

// Upload a single file
$fileChooser->setInputFiles(__DIR__.'/fixtures/avatar.jpg');

// Upload multiple files
$fileChooser->setInputFiles([
    __DIR__.'/fixtures/document1.pdf',
    __DIR__.'/fixtures/document2.docx',
]);
```

## Network Interception

Playwright allows you to intercept, inspect, and modify network requests made by the page. This is incredibly useful for
testing edge cases, mocking API responses, or blocking unwanted resources.

You can use `$page->route()` to intercept requests that match a specific URL pattern.

```php
// Mock an API response
$this->page->route('**/api/v1/users/me', function ($route) {
    $route->fulfill([
        'status' => 200,
        'contentType' => 'application/json',
        'body' => json_encode([
            'id' => 123,
            'name' => 'Mock User',
            'email' => 'mock@example.com',
        ]),
    ]);
});

// Navigate to the page that makes the API call
$this->page->goto('/profile');

// Assert that the mocked data is displayed
expect($this->page->locator('.username'))->toHaveText('Mock User');
```

You can also use `$route->abort()` to block requests, for example, to test how your site behaves without analytics
scripts or third-party fonts.

## Handling Dialogs

Your application might trigger JavaScript dialogs like `alert`, `confirm`, or `prompt`. Playwright can listen for these
and handle them automatically.

```php
// Listen for the next dialog that appears
$this->page->events()->onDialog(function ($dialog) {
    // Assert the dialog message is correct
    $this->assertSame('Are you sure you want to delete this item?', $dialog->message());

    // Accept the dialog
    $dialog->accept();
});

// Perform the action that triggers the dialog
$this->page->locator('#delete-item-button')->click();
```

## Working with Frames

If your page uses `<iframe>` elements, you can interact with their content using `$page->frameLocator()`. This returns a
`FrameLocator`, which has a `.locator()` method to find elements specifically within that frame.

```php
// Create a locator for the iframe
$frame = $this->page->frameLocator('#my-iframe');

// Now find elements within that frame
$frameInput = $frame->locator('#name-input');
$frameInput->fill('This is inside a frame');

$frameButton = $frame->locator('button:has-text("Submit")');
$frameButton->click();
```

## Browser Configuration with `PlaywrightConfigBuilder`

For advanced browser setups, such as using a proxy server or setting custom launch arguments, you can use the
`PlaywrightConfigBuilder`.

This is typically done outside of the test case itself, when you are creating your `PlaywrightClient` instance.

```php
use PlaywrightPHP\PlaywrightFactory;
use PlaywrightPHP\Configuration\PlaywrightConfigBuilder;

// Create a custom configuration
$config = PlaywrightConfigBuilder::create()
    ->withHeadless(false)
    ->withSlowMoMs(50)
    ->addArg('--start-maximized')
    ->withProxy('http://myproxy.com:8080')
    ->build();

// Create a Playwright client with the custom config
$playwright = PlaywrightFactory::create($config);

// Now launch the browser, which will use these settings
$browser = $playwright->chromium()->launch();
```
