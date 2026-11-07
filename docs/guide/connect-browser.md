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

### Configure a remote endpoint

Set `wsEndpoint` to make `launch()` connect to a remote Playwright browser instead of starting a local browser:

```php
use Playwright\Configuration\PlaywrightConfig;
use Playwright\PlaywrightFactory;

$config = new PlaywrightConfig(wsEndpoint: 'ws://127.0.0.1:3000/');
$playwright = PlaywrightFactory::create($config);
$builder = $playwright->chromium();
$browser = $builder->launch();
$context = $browser->context();
$page = $context->newPage();
$page->goto('https://example.com');

echo $page->title().PHP_EOL;

$browser->close();
$playwright->close();
```

The builder also accepts `withWsEndpoint($endpoint)`. To read the endpoint from the environment, use `PlaywrightConfigBuilder::fromEnv()->build()`: `PW_WS_ENDPOINT` takes precedence over `PLAYWRIGHT_WS_ENDPOINT`, and an empty `PW_WS_ENDPOINT` falls back to the latter. Setting either variable alone does not change `PlaywrightFactory::create()`; pass the resulting configuration to the factory.

```bash
PW_WS_ENDPOINT='ws://playwright:3000/' php your-script.php
```

The browser type must match the server. Set browser launch options such as `headless`, `channel`, `args` and `proxy` on the server. For a remote `launch()`, `timeoutMs` and `slowMoMs` apply to the connection, and context settings apply to the initial context.

An explicit `connect($endpoint, $options)` call uses the supplied endpoint and options and creates its initial context with Playwright defaults. Use `newContext()` to create a context with your configured settings.

### Run the browser in a separate container

For example, start a Playwright 1.64.1 server with its matching browser image:

```bash
docker run --rm --init --ipc=host -p 3000:3000 \
    --workdir /home/pwuser --user pwuser \
    mcr.microsoft.com/playwright:v1.64.1-noble \
    /bin/sh -c "npx -y playwright@1.64.1 run-server --port 3000 --host 0.0.0.0"
```

Connect to `ws://127.0.0.1:3000/` from the host, or use the container's service name from another container on the same network. The PHP container still needs Node.js and the Playwright npm package for the bridge; browser binaries and their system dependencies only need to be installed in the browser container. Check the bridge's version with `node -p 'require("./vendor/playwright-php/playwright/bin/node_modules/playwright/package.json").version'` from your project root and use a server with the same major and minor version, as required by [Playwright's connection protocol](https://playwright.dev/docs/api/class-browsertype#browser-type-connect).

URLs opened by the browser must be reachable from its container. `localhost` refers to that container, so use your application's service name or an address reachable from it. See the [Playwright Docker guide](https://playwright.dev/docs/docker#remote-connection) for server and network setup.

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
$context = $browser->context();
$page = $context->pages()[0] ?? $context->newPage();
$page->goto('https://example.com');

echo $page->title().PHP_EOL;

$browser->close();
$playwright->close();
```

`$browser->context()` is the browser's existing context, and `pages()` lists the pages already open in it. Closing the client disconnects from the browser and leaves those pages open, so a later client finds them. A context from `$browser->newContext()` is destroyed with its pages on disconnect.

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
