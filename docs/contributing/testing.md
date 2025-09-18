# Playwright Testing

This short guide summarizes the current test runtime patterns used by the integration suite.

## Route-Based Test Content (no `php -S`)

- Use `tests/Support/RouteServerTestTrait` to fulfill requests directly via Playwright routing.
- This removes the need to spawn a PHP built-in web server in tests.

Example

```php
use Playwright\Tests\Support\RouteServerTestTrait;

// Inside a test case using PlaywrightTestCaseTrait
$this->installRouteServer($this->page, [
    '/index.html' => '<h1>Hello</h1>',
    '/script.js'  => 'window.ok = true;',
]);

$this->page->goto($this->routeUrl('/index.html'));
```

Notes
- `routeUrl('/path')` returns a stable URL (e.g. `http://localhost:31817/path`).
- Prefer `page->route(...)` inside the test if you need to override a response for a single test.

## Optional Tracing

- Tracing is disabled by default to keep the suite fast and light.
- Enable per-run by setting `PW_TRACE=1` (screenshots and snapshots enabled for each test).
  - macOS/Linux: `PW_TRACE=1 composer test:integration`
  - Windows (PowerShell): `$env:PW_TRACE='1'; composer test:integration`
- On failure, artifacts are written to `test-failures/`.

## Shared Lifecycle for Speed

- The test trait reuses a single Playwright client and browser per test class by default.
- Each test still uses a fresh `BrowserContext` + `Page`, which are closed in `tearDown()`.
- If a test needs a custom `PlaywrightConfig` (e.g., `screenshotDir`), pass it to `setUpPlaywright(...)` — in that case, the test runs with a dedicated client/browser (bypasses sharing) to respect the custom config.

## Performance Tips

- Keep `PW_TRACE` off unless diagnosing failures.
- Prefer Playwright waits (e.g., `waitForSelector`, `waitForResponse`) over `usleep()`.
- When iterating locally, run integration tests without coverage for further speedups.

## Expected Processes

- A single Node.js Playwright server process (spawned by the transport).
- A browser process (Chromium/Firefox/WebKit) per launched browser.
- Minimal short‑lived processes in specific tests (e.g., `ProcessLauncherTest`).

