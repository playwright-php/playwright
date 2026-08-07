# CHANGELOG

## [Unreleased]

### Added
- `BrowserContextInterface::clock()`, `setGeolocation()` and `setOffline()`
- `DialogInterface::page()`
- `KeyboardInterface::insertText()`
- `PageInterface::pause()`
- `ResponseInterface::headerValue()`
- `expect($locator)->toBeAttached()`

### Changed
- `Testing\Expect` delegates to `LocatorAssertions` and `PageAssertions`, sharing one auto-waiting and tracing implementation

### Fixed
- `expect()->toHaveClass()` matches the class attribute exactly
- `expect()->toBeEmpty()` evaluates input values and text content in the DOM
- `expect()->not()` applies to one assertion instead of leaking to later assertions on the same object

## [1.3.1] - 2026-08-04

### Fixed
- `Locator::setInputFiles()` and `Page::setInputFiles()` resolve relative paths before sending them to the Node bridge (#127)
- Endpoint credentials are redacted from the `connect()` and `connectOverCDP()` logs (#128)

## [1.3.0] - 2026-07-23

### Added
- `Page::waitForFunction()` (#87)
- Clear cookies by name (#85)
- `BrowserContextInterface::tracing()` exposing the Tracing API; `expect()` assertions are recorded as named trace groups (#114)
- `BrowserBuilder::withChannel()`, `withProxy()` and `withDownloadsPath()` (#121)
- PSR Log 2.0 support, alongside 3.0 (#93)
- Interface `@method` annotations for methods already shipped by the concrete classes: `BrowserContext::clock()`, `BrowserContext::setGeolocation()`, `BrowserContext::setOffline()`, `Dialog::page()`, `Keyboard::insertText()`, `Page::pause()`, `Response::headerValue()`. They move to real interface declarations in the next major (#108)

### Changed
- `PlaywrightConfig` is now mandatory on the `Browser`, `BrowserContext`, `Page` and `BrowserBuilder` constructors (#72)
- `Page::getBy*()` locators accept `string|Regex`, matching the Playwright JS API (#76)
- `PW_TRACE` is the single tracing switch; contexts created from `PlaywrightConfigBuilder::fromEnv()` record a trace saved on close (#111)
- `PlaywrightConfig` applies `channel`, `proxy`, `downloadsDir`, `videosDir` and `minNodeVersion`; `videosDir` also applies to the default context (#121)

### Fixed
- `PageInterface::waitForSelector()` returns `LocatorInterface` instead of `?LocatorInterface`, matching the implementation (#74)
- `Page::bringToFront()` is implemented (#89)
- `Page::unroute()` reaches the page instead of the context (#110)
- `Page::setDefaultTimeout()` and `setDefaultNavigationTimeout()` are registered server-side (#109)
- `BrowserContext::setStorageState()` is registered server-side (#112)
- Operation timeouts extend the RPC deadline (#113)
- Request bodies survive non-UTF8 content, carried as base64 `postDataBuffer` (#116)
- The Node bridge and its browser shut down when the PHP process dies (#118)
- `LspFraming` recovers from stray non-LSP output on the stream (#106)
- `ProcessJsonRpcClient` clears the Process output buffers (#103)
- Passive popup and tab registration in the Node bridge (#104)
- `waitForActionable` honours the options passed to override the timeout (#82)
- Proxy credentials are redacted from the "Launching browser" log (#121)

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
