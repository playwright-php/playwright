# Visual Regression

`toHaveScreenshot()` compares a screenshot of a page or an element with a baseline image stored next to your tests.
It catches what the other assertions cannot see: a broken layout, a wrong color, a font that stopped loading.

```php
public function testCardLooksTheSame(): void
{
    $this->page->goto('/design-system/card');

    $this->expect($this->page->getByTestId('preview'))->toHaveScreenshot('card.webp');
}
```

## Why compare pixels

Every other assertion reads the DOM: `toBeVisible()`, `toHaveText()` and `toHaveCSS()` check one element and one property
at a time. What the user sees is the rendered result of all of them together, and the two can disagree. A page where
every element is attached, visible and correctly labelled can still look broken: only the rendered pixels tell.

A screenshot comparison checks that rendered result as a whole. It catches the bugs no DOM assertion is written for:

- **CSS side effects.** A margin changed in a shared stylesheet, or a design token renamed, and a card three pages away
  overflows its container.
- **Design system drift.** A button variant loses its border radius, the dark theme picks the wrong background, an icon
  moves by 4 pixels.
- **Assets and fonts.** A web font fails to load and the fallback reflows the page, an SVG icon goes missing, an image
  is stretched.
- **Layout at breakpoints.** Text wraps differently, elements overlap, a sticky header covers content on a narrow
  viewport.
- **Upgrades.** A new version of a CSS framework, a UI component library or the browser itself, with every visual
  consequence visible in one run.

Describing "this looks right" with DOM assertions would take hundreds of `toHaveCSS()` checks per component, and they
would still miss how elements interact. One screenshot covers it, and the diff image points at the exact place that
changed: cheap to write, fast to review.

### A defensive test, on chosen surfaces

Visual regression is a guard against unwanted change. It belongs on the few surfaces whose appearance is part of the
contract: the design system, the pages that carry the product. Every baseline is a file to review each time the
design moves on purpose, so screenshotting every page of an application turns each redesign into a chore and each
failure into noise. A handful of well-chosen baselines stays meaningful: when one fails, something that mattered
changed.

### Where it pays off

- **A component gallery.** One page renders each component in every variant, one screenshot per component and theme.
  A data provider turns it into a matrix:

  ```php
  #[DataProvider('components')]
  public function testComponentLooksTheSame(string $name, string $theme): void
  {
      $this->page->emulateMedia(['colorScheme' => $theme]);
      $this->page->goto('/design-system/'.$name);

      $this->expect($this->page->getByTestId('preview'))->toHaveScreenshot(sprintf('%s-%s.webp', $name, $theme));
  }

  public static function components(): iterable
  {
      foreach (['button', 'badge', 'alert', 'card', 'modal'] as $name) {
          foreach (['light', 'dark'] as $theme) {
              yield sprintf('%s %s', $name, $theme) => [$name, $theme];
          }
      }
  }
  ```

- **Precious pages.** Login, checkout, landing pages: the screens where a visual break costs money or trust.
- **Refactors and upgrades.** Record the baselines, change the code, and see exactly what moved.

### What it complements

A screenshot checks appearance. Clicking, submitting and navigating stay the job of behavior assertions, which prove
the application works; visual regression proves it still looks the way it was designed. Pages full of dynamic content
(dates, avatars, ads, user data) need masks or fixed fixtures to compare reliably, and baselines need one reference
system, since fonts render differently from one system to another. Both are covered below.

## How it works

The comparison runs in Playwright itself, with the same code as `toHaveScreenshot()` in `@playwright/test`:

1. Playwright takes screenshots until two consecutive ones are identical. Animations are disabled, the text caret is
   hidden and the image uses CSS pixels, so the page has a chance to settle.
2. The stable screenshot is compared with the baseline, pixel by pixel, within the tolerance you configure.
3. When they differ, the expected, actual and diff images are written to the test's own directory under
   `test-failures/`, and the assertion fails with their paths.

## Baselines

The first run has nothing to compare with: it writes the baseline and fails, so a new screenshot is always reviewed
before it is trusted. Run the test again and it passes.

A relative name is stored next to the test file, suffixed with the browser and the platform, like `@playwright/test`
does. Both are read from the page under test, nothing to configure. Browsers and systems render fonts differently, so
each combination keeps its own baseline:

```
tests/E2E/CardTest.php
tests/E2E/CardTest.php-snapshots/card-chromium-darwin.webp
tests/E2E/CardTest.php-snapshots/card-chromium-linux.webp
tests/E2E/CardTest.php-snapshots/card-firefox-linux.webp
```

A persistent context has no browser object: its baselines carry the platform only. An absolute path is used as is. To
store baselines elsewhere, override `snapshotDirectory()` in your test case.

Commit the baselines. Generate the ones used in CI on the same system as CI, ideally in the same Docker image.

Record and compare in headless mode, the default and what CI runs. Both headless modes of Chromium, the headless shell
and `channel: 'chromium'`, produce byte-identical screenshots. A visible browser shows a scrollbar as soon as the page
scrolls, 15 pixels that fail any full-page comparison against a headless baseline.

### Failure images

Each test writes its failure images in its own directory, one per browser, as Playwright JS does per test and project
under `test-results/`: `test-failures/<TestClass>-<testMethod>-<browser>/`. Override `testOutputDirectory()` in your
test case to move it. Each baseline has a subdirectory with its name and a short identifier derived from its path.
This keeps `light/card.png`, `dark/card.png` and `card.webp` separate, including when a passing assertion cleans up
its earlier failure. The image names omit the browser, which the test directory already identifies.
For `toHaveScreenshot('card.png')` in `CardTest::testCard`, run in Chromium, the baseline is
`card-chromium-linux.png` and a failure writes to `test-failures/CardTest-testCard-chromium/card-<id>/`:

| File | Content |
|---|---|
| `card-expected.png` | Copy of the baseline at the time of the failure |
| `card-actual.png` | Last screenshot taken |
| `card-diff.png` | Baseline in pale grey, differing pixels in red. Always a PNG |

Expected and actual keep the format of the baseline, `.webp` included. The three files are removed as soon as the
baseline matches again or is recorded again, and the directory with them once empty: a green run leaves a clean
`test-failures/`.

What happens to the baseline itself:

| Situation | `missing` (default) | `none` | `changed` | `all` |
|---|---|---|---|---|
| No baseline | written, test fails | test fails | written, test passes | written, test passes |
| Baseline matches | kept, test passes | kept, test passes | kept, test passes | rewritten, test passes |
| Baseline differs | kept, failure images, test fails | kept, failure images, test fails | rewritten, test passes | rewritten, test passes |
| Page never stable, baseline present | kept, failure images, test fails | kept, failure images, test fails | kept, failure images, test fails | kept, test fails |
| Page never stable, no baseline | nothing written, test fails | nothing written, test fails | nothing written, test fails | nothing written, test fails |

### Prefer WebP

The extension of the name picks the format. Without extension, `.png` is added, as in `@playwright/test`. We recommend
`.webp`: Playwright records it lossless, pixel for pixel the same image as the PNG.

```php
$this->expect($this->page)->toHaveScreenshot('dashboard.webp');
```

| | PNG | WebP |
|---|---|---|
| Size, flat interface | 12 KB | 3 KB |
| Size, viewport with gradients | 357 KB | 24 KB |
| Comparison, viewport | 88 ms | 21 ms |
| Comparison, full page | 423 ms | 153 ms |

A repository that stores hundreds of baselines stays light, and the suite runs faster since decoding dominates the
comparison. PNG keeps one advantage: more review tools preview it in a diff. The diff image of a failure is always a PNG.
Other extensions, JPEG included, throw an `InvalidArgumentException`: only lossless formats compare reliably.

### Updating baselines

The `PLAYWRIGHT_UPDATE_SNAPSHOTS` variable takes the values of the `--update-snapshots` flag of `@playwright/test`:

| Value | Missing baseline | Different screenshot |
|---|---|---|
| `missing` (default) | written, the test fails | the test fails |
| `none` | the test fails | the test fails |
| `changed` | written, the test passes | rewritten, the test passes |
| `all` | written, the test passes | rewritten, matching ones too |

```shell
PLAYWRIGHT_UPDATE_SNAPSHOTS=changed vendor/bin/phpunit tests/E2E
```

Then review the changed images in your diff before committing them.

## Options

Pass a `ToHaveScreenshotOptions` with named arguments to tune the comparison:

```php
use Playwright\Assertions\Options\ToHaveScreenshotOptions;

$this->expect($this->page)->toHaveScreenshot('dashboard.webp', new ToHaveScreenshotOptions(
    fullPage: true,
    maxDiffPixelRatio: 0.01,
    mask: [$this->page->locator('.timestamp'), $this->page->locator('.avatar')],
));
```

The defaults are the ones of `@playwright/test`:

| Option | Default | Effect |
|---|---|---|
| `threshold` | `0.2` | Color distance tolerated for each pixel, from 0 (exact) to 1. Used by pixelmatch only |
| `maxDiffPixels` | none | Number of differing pixels tolerated |
| `maxDiffPixelRatio` | none | Share of differing pixels tolerated, from 0 to 1 |
| `comparator` | `'pixelmatch'` | `'ssim-cie94'` compares structure and perceived color, experimental in Playwright |
| `mask` | `[]` | Locators covered with a solid box, for dates, avatars, ads. Inside an iframe, use `frameLocator()` |
| `maskColor` | `'#FF00FF'` | Any CSS color |
| `style` | none | CSS applied while taking the screenshot, to hide or freeze dynamic parts |
| `fullPage` | `false` | Captures the whole scrollable page. Pages only |
| `clip` | none | Region to capture: `['x' => 0, 'y' => 0, 'width' => 320, 'height' => 200]`. Pages only |
| `omitBackground` | `false` | Transparent background instead of white |
| `animations` | `'disabled'` | Finite animations jump to their end, infinite ones are cancelled. `'allow'` keeps them running |
| `caret` | `'hide'` | `'initial'` keeps the blinking text caret |
| `scale` | `'css'` | One pixel per CSS pixel, identical on every screen. `'device'` is sharper on high-DPI screens |
| `timeoutMs` | assertion timeout | Time allowed to get two identical consecutive screenshots |
| `message` | generated | Replaces the first line of the failure message |

Invalid values throw an `InvalidArgumentException` when the options are created, so a typo such as `comparator: 'ssim'`
fails in PHP with the list of accepted values. Numeric options must be finite: `NAN` and infinities are rejected before
reaching the browser. On a locator, `fullPage` and `clip` throw too: an element is captured whole.

### Tolerating differences

Without tolerance, a single differing pixel fails the assertion. `maxDiffPixels` suits a fixed-size element,
`maxDiffPixelRatio` a page whose size varies. When both are set, the stricter one applies.

### Choosing a threshold

The default `threshold` of `0.2` forgives anti-aliasing and small rendering noise. It also forgives close shades of one
hue: `#818cf8` and `#6366f1`, two indigos of the same palette, compare as identical. To catch a design token change, lower
it to `0.1` or switch to the `ssim-cie94` comparator, which ignores `threshold`.

## Limits

- Negation throws a `LogicException`: a screenshot either matches its baseline or is reviewed.
- Decorated pages and test doubles throw a `LogicException` too. The comparison needs the `Page` and `Locator` created
  by Playwright PHP.
