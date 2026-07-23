# CHANGELOG

## [Unreleased]

### Added
- Interface annotations (`@method`, no BC impact) for methods already shipped by the
  concrete classes: `BrowserContext::clock()`, `BrowserContext::setGeolocation()`,
  `BrowserContext::setOffline()`, `Dialog::page()`, `Keyboard::insertText()`,
  `Page::pause()`, `Response::headerValue()`. They will move to real interface
  declarations in the next major.
- `BrowserBuilder::withChannel()`, `withProxy()` and `withDownloadsPath()`

### Fixed
- `PlaywrightConfig` now applies `channel`, `proxy`, `downloadsDir`,
  `videosDir` and `minNodeVersion`. They were declared but never read, so
  setting them had no effect. Anyone who worked around the `proxy` gap by
  passing `--proxy-server` in `args` will now get both.
- `videosDir` applies to the default context as well, not only to contexts
  created through `Browser::newContext()`. The server builds the default
  context during launch, so its options now travel with the launch command.
- Proxy credentials no longer appear in the "Launching browser" log entry

## [1.2.0] - 2026-02-25

### Added
- `PLAYWRIGHT_BROWSERS_PATH` is read by `PlaywrightConfigBuilder::fromEnv()` and
  forwarded to the Node.js server, so a shared browser installation can be
  reused instead of a per-project one (#71)

### Fixed
- `Page::waitForLoadState()` no longer fails with `Unknown action`: the action
  was never registered in the Node bridge (#65)
- Cookie expiry accepts an integer or a float timestamp (#67)

## [1.1.0] - 2025-12-23

### Added
- PDF generation support via `Page::pdf()`
- Typed `Options` classes (e.g., `ClickOptions`, `PdfOptions`)
- Frame, Locator and Selector implements `Stringable`

## [1.0.0] - 2025-11-08

### Added
- Initial stable release of Playwright PHP
- Cross-browser support (Chromium, Firefox, WebKit)
- PHPUnit integration with fluent assertions
- Auto-waiting locators and interactions
- Screenshot and tracing capabilities
- Storage state management

### Changed
- Marked package as stable (removed experimental warning)
- Added PHPUnit 10+ requirement documentation for testing trait
