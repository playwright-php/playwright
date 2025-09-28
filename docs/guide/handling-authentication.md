# Handling Authentication

Most web applications require users to log in. Automating this process is a fundamental requirement for any end-to-end
testing suite. Playwright for PHP provides powerful and flexible tools to handle authentication efficiently.

There are two primary strategies for handling authentication in your tests:

1. **UI Login:** Perform a login by interacting with the login form, just as a user would.
2. **Reusing Authentication State:** Log in once, save the session state, and then reuse it across multiple tests. *
   *This is the recommended approach for most scenarios.**

## Strategy 1: UI Login

This strategy is straightforward and is most useful when you are specifically testing the login and logout functionality
of your application.

The implementation involves navigating to the login page, filling in the credentials, and submitting the form.

```php
public function testUserCanLogIn(): void
{
    $this->page->goto('https://my-app.com/login');

    $this->page->locator('#username')->fill('test-user');
    $this->page->locator('#password')->fill('password123');
    $this->page->locator('button[type="submit"]')->click();

    // Assert that the user is redirected to the dashboard
    expect($this->page)->toHaveURL('https://my-app.com/dashboard');
}
```

While simple, this approach can be slow if you need to log in before every single test.

## Strategy 2: Reusing Authentication State (Recommended)

A much faster and more reliable approach is to separate the login process from your tests. You log in once, save the
browser's session state (which includes cookies and local storage), and then load this state into a new browser context
for each test. This way, your tests can start in an already authenticated state.

### Step 1: Create a Script to Save the State

First, you need a standalone script that performs the login and saves the authentication state to a file. You only need
to run this script once, or whenever your login credentials change.

**`setup_auth.php`**

```php
<?php
require __DIR__.'/vendor/autoload.php';
use Playwright\Playwright;

$context = Playwright::chromium();
$page = $context->newPage();

$page->goto('https://my-app.com/login');
$page->locator('#username')->fill('test-user');
$page->locator('#password')->fill('password123');
$page->locator('button[type="submit"]')->click();

// Wait for the navigation to the dashboard to complete
$page->waitForURL('**/dashboard');

// Save the storage state to a file
$context->saveStorageState(__DIR__.'/auth.json');

$context->close();

echo "Authentication state saved successfully to auth.json\n";
```

Run this script from your terminal: `php setup_auth.php`.

### Step 2: Load the State in Your Tests

Now, you can configure your `PlaywrightTestCase` to use this saved state. The easiest way is to create a base test case
for your authenticated tests that overrides the `setUp` method.

**`AuthenticatedTestCase.php`**

```php
<?php
namespace App\Tests;

use Playwright\Testing\PlaywrightTestCase;

abstract class AuthenticatedTestCase extends PlaywrightTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load the saved authentication state before each test
        $this->context->loadStorageState(__DIR__.'/../auth.json');
    }
}
```

Now, any test that extends `AuthenticatedTestCase` will start as a logged-in user.

**`ProfilePageTest.php`**

```php
<?php
namespace App\Tests;

use function Playwright\Testing\expect;

class ProfilePageTest extends AuthenticatedTestCase
{
    public function testUserProfileShowsCorrectUsername(): void
    {
        // No need to log in here; we are already authenticated.
        $this->page->goto('https://my-app.com/profile');

        expect($this->page->locator('.username-display'))->toHaveText('test-user');
    }
}
```

### When to Use Each Strategy

* Use **UI Login** when you are specifically testing the login, password-reset, or logout features.
* Use **Reusing Authentication State** for all other tests that require an authenticated user. It will make your test
  suite significantly faster and more stable.
