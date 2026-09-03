# Browsers, Browser Types, and Channels

Playwright PHP can launch Playwright-managed Chromium, Firefox, and WebKit. It
can also launch supported Google Chrome and Microsoft Edge distributions through
Chromium channels. Installation and launch configuration are separate choices.

## Choose what to install

Install Playwright's default managed browser set:

```bash
vendor/bin/playwright-install --browsers
```

This installs Chromium, Firefox, and WebKit, together with the support binaries
required by the bundled Playwright version.

Install only the browser targets your project needs by passing their names:

```bash
vendor/bin/playwright-install chromium
vendor/bin/playwright-install chromium webkit
```

The installer accepts these targets:

| Target | Installation |
| --- | --- |
| `chromium` | Playwright-managed Chromium |
| `firefox` | Playwright-managed Firefox |
| `webkit` | Playwright-managed WebKit |
| `chrome` | Google Chrome stable |
| `chrome-beta` | Google Chrome Beta |
| `msedge` | Microsoft Edge stable |
| `msedge-beta` | Microsoft Edge Beta |

`--browsers` cannot be combined with explicit targets. Use one form or the
other.

The installer does not treat browser names as aliases. In particular, `chrome`
installs Google Chrome and does not install Playwright-managed Chromium. There
is no `safari` target because Playwright uses a patched WebKit build, not the
installed Safari application.

## Understand branded browser installation

Google Chrome and Microsoft Edge are installed in the operating system's global
location. Playwright warns that this can replace an existing installation.
`PLAYWRIGHT_BROWSERS_PATH` does not redirect branded browser installations.

Use a branded browser when the test specifically needs its stable or beta
distribution. For general automation, Playwright recommends its managed
Chromium build.

## Choose a browser type at runtime

Browser types are the three Playwright API families used to launch browsers:

| PHP method | Browser type | Default installation |
| --- | --- | --- |
| `$playwright->chromium()` | Chromium | `chromium` |
| `$playwright->firefox()` | Firefox | `firefox` |
| `$playwright->webkit()` | WebKit | `webkit` |

`Playwright::safari()` is an existing alias for WebKit. It does not automate
Apple Safari.

Installing several browsers does not make a script run in all of them. Each
launch still selects one browser type.

## Launch a Chromium channel

A channel selects a Chromium distribution at launch. It is not another browser
type.

```php
<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use Playwright\Configuration\PlaywrightConfigBuilder;
use Playwright\PlaywrightFactory;

$config = PlaywrightConfigBuilder::create()
    ->withChannel('chrome')
    ->build();

$playwright = PlaywrightFactory::create($config);
$browser = $playwright->chromium()->launch();
```

Install the matching branded target before using a channel on a machine where
that browser is absent:

```bash
vendor/bin/playwright-install chrome
```

`PW_CHANNEL=chrome` provides the same launch choice when configuration is built
with `PlaywrightConfigBuilder::fromEnv()`.

## Keep the browser cache consistent

Set `PLAYWRIGHT_BROWSERS_PATH` during installation and at runtime when managed
browsers use a custom cache:

```bash
PLAYWRIGHT_BROWSERS_PATH=var/playwright-browsers vendor/bin/playwright-install chromium
```

Browser revisions are tied to the Playwright version. Run the installer again
after updating Playwright when its required browser revisions change.

See the official [Playwright browser guide](https://playwright.dev/docs/browsers)
for current browser behavior and platform support.
