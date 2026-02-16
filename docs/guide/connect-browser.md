# Connecting to External Browsers

This guide explains how to connect Playwright PHP to a browser that is already running outside your current PHP process.

Use one of these connection modes:

- **Playwright protocol (`connect`)**: connect to a browser server started with `launchServer()` (recommended).
- **Chrome DevTools Protocol (`connectOverCDP`)**: connect to Chromium/Chrome with remote debugging enabled.

## Connect with Playwright protocol (recommended)

This mode gives the most complete Playwright behavior and works with browser server endpoints returned by
`wsEndpoint()`.

### Step 1: Start an external browser server instance

Run this in another PHP process:

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Playwright\PlaywrightFactory;

$playwright = PlaywrightFactory::create();
$server = $playwright->launchServer('chromium', ['headless' => true]);

echo $server->wsEndpoint().PHP_EOL;

while (true) {
    sleep(1);
}
```

Copy the printed `ws://...` value exactly as-is (including the random path segment).

### Step 2: Connect from your script

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Playwright\PlaywrightFactory;

$playwright = PlaywrightFactory::create();
$endpoint = getenv('REMOTE_WS_ENDPOINT'); // ws://127.0.0.1:PORT/ID

if (false === $endpoint || '' === $endpoint) {
    throw new \RuntimeException('Set REMOTE_WS_ENDPOINT to the wsEndpoint() value.');
}

$browser = $playwright->chromium()->connect($endpoint);
$context = $browser->newContext();
$page = $context->newPage();
$page->goto('https://example.com');

echo $page->title().PHP_EOL;

$browser->close();
$playwright->close();
```

Run:

```bash
REMOTE_WS_ENDPOINT='ws://127.0.0.1:PORT/ID' php your-script.php
```

## Connect over CDP (Chromium only)

Use this when you already have Chrome/Chromium running with remote debugging.

### Step 1: Start Chrome with remote debugging

macOS:

```bash
"/Applications/Google Chrome.app/Contents/MacOS/Google Chrome" --remote-debugging-port=9222 --user-data-dir=/tmp/chrome-pw
```

### Step 2: Connect with `connectOverCDP()`

```php
<?php

require __DIR__.'/vendor/autoload.php';

use Playwright\PlaywrightFactory;

$playwright = PlaywrightFactory::create();
$cdpEndpoint = getenv('CDP_ENDPOINT') ?: 'http://127.0.0.1:9222';

$browser = $playwright->chromium()->connectOverCDP($cdpEndpoint);
$context = $browser->newContext();
$page = $context->newPage();
$page->goto('https://example.com');

echo $page->title().PHP_EOL;

$browser->close();
$playwright->close();
```

## Troubleshooting

### `ECONNREFUSED` when connecting

- The target browser endpoint is not running.
- Start the server/browser first, then retry.
- Use `127.0.0.1` instead of `localhost` if your machine resolves `localhost` to IPv6 and nothing listens on `::1`.

### `connect()` fails on a CDP endpoint

- `connect()` requires a **Playwright WebSocket endpoint** from `launchServer()`.
- If you only have `http://127.0.0.1:9222`, use `connectOverCDP()` instead.

## Related examples

- `docs/examples/connect_external_browser.php`
- `docs/examples/connect_remote_browser.php`
- `docs/examples/launch_server.php`
