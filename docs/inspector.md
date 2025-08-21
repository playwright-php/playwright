# Playwright Inspector (PHP)

Use Playwright Inspector to step through actions, inspect locators, and evaluate scripts.

Quick start:
- Builder: `$browser = $playwright->chromium()->withHeadless(false)->withInspector()->launch();`
- Pause anywhere: `$page->pause();`
- Or via environment: `PWDEBUG=1 php your_script.php`

Example:
- `php docs/examples/example_inspector.php`

Notes:
- Inspector is controlled by the Node server; `PWDEBUG` is forwarded automatically.
- Run headed (`headless: false`) to see browser UI while debugging.
- For CI/Linux without a display, use Xvfb.
