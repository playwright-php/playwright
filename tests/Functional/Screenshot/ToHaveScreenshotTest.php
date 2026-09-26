<?php

declare(strict_types=1);

/*
 * This file is part of the community-maintained Playwright PHP project.
 * It is not affiliated with or endorsed by Microsoft.
 *
 * (c) 2025-Present - Playwright PHP - https://github.com/playwright-php
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Playwright\Tests\Functional\Screenshot;

use PHPUnit\Framework\Attributes\CoversClass;
use Playwright\Assertions\Failure\AssertionException;
use Playwright\Assertions\Internal\ScreenshotExpectation;
use Playwright\Assertions\LocatorAssertions;
use Playwright\Assertions\Options\ToHaveScreenshotOptions;
use Playwright\Assertions\PageAssertions;
use Playwright\Locator\Locator;
use Playwright\Page\Page;
use Playwright\Screenshot\ScreenshotComparison;
use Playwright\Testing\Expect;
use Playwright\Tests\Functional\FunctionalTestCase;

#[CoversClass(PageAssertions::class)]
#[CoversClass(LocatorAssertions::class)]
#[CoversClass(ScreenshotExpectation::class)]
#[CoversClass(ScreenshotComparison::class)]
#[CoversClass(Expect::class)]
#[CoversClass(Page::class)]
#[CoversClass(Locator::class)]
final class ToHaveScreenshotTest extends FunctionalTestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/pw_to_have_screenshot_'.uniqid();
        unset($_SERVER[ScreenshotExpectation::UPDATE_ENV]);
    }

    protected function tearDown(): void
    {
        unset($_SERVER[ScreenshotExpectation::UPDATE_ENV]);
        $this->remove($this->dir);
        foreach (glob(getcwd().'/test-failures/ToHaveScreenshotTest-*') ?: [] as $dir) {
            $this->remove($dir);
        }

        parent::tearDown();
    }

    protected function snapshotDirectory(): string
    {
        return $this->dir;
    }

    public function testFirstRunWritesTheBaselineThenMatches(): void
    {
        $this->card('#818cf8');
        $options = new ToHaveScreenshotOptions(mask: [$this->page->locator('.time')]);

        try {
            $this->expect($this->page)->toHaveScreenshot('page.png', $options);
            $this->fail('The first run must fail after writing the baseline.');
        } catch (AssertionException $e) {
            $this->assertStringContainsString('writing actual', $e->getMessage());
        }

        $baseline = $this->dir.'/page-'.$this->variant().'.png';
        $this->assertFileExists($baseline);
        $this->assertSame("\x89PNG", substr((string) file_get_contents($baseline), 0, 4));

        $this->card('#818cf8');
        $this->expect($this->page)->toHaveScreenshot('page.png', $options);
    }

    public function testTimestampsAndInfiniteAnimationsAreStabilized(): void
    {
        $this->card('#818cf8');
        $card = $this->page->locator('#card');
        $options = new ToHaveScreenshotOptions(mask: [$this->page->locator('.time')]);
        $this->record(fn () => $this->expect($card)->toHaveScreenshot('card.png', $options));

        usleep(20_000);
        $this->card('#818cf8');
        $this->expect($card)->toHaveScreenshot('card.png', $options);
    }

    public function testChangedElementFailsAndKeepsTheImagesUntilItPassesAgain(): void
    {
        $this->card('#818cf8', 'Hello');
        $card = $this->page->locator('#card');
        $options = new ToHaveScreenshotOptions(mask: [$this->page->locator('.time')]);
        $this->record(fn () => $this->expect($card)->toHaveScreenshot('card.png', $options));

        $this->card('#818cf8', 'Hello world');

        try {
            $this->expect($card)->toHaveScreenshot('card.png', $options);
            $this->fail('A changed element must fail the assertion.');
        } catch (AssertionException $e) {
            $this->assertStringContainsString('Screenshot of the locator does not match', $e->getMessage());
            $this->assertStringContainsString('pixels', $e->getMessage());
        }

        $out = $this->testOutputDirectory().'-chromium/card';
        $this->assertStringEndsWith('/test-failures/ToHaveScreenshotTest-testChangedElementFailsAndKeepsTheImagesUntilItPassesAgain-chromium/card', $out);
        $this->assertFileExists($out.'-expected.png');
        $this->assertFileExists($out.'-actual.png');
        $this->assertFileExists($out.'-diff.png');

        $this->card('#818cf8', 'Hello');
        $this->expect($card)->toHaveScreenshot('card.png', $options);

        $this->assertFileDoesNotExist($out.'-expected.png');
        $this->assertFileDoesNotExist($out.'-actual.png');
        $this->assertFileDoesNotExist($out.'-diff.png');
    }

    public function testDefaultThresholdToleratesCloseShadesOfOneHue(): void
    {
        $this->card('#818cf8');
        $card = $this->page->locator('#card');
        $options = new ToHaveScreenshotOptions(mask: [$this->page->locator('.time')]);
        $this->record(fn () => $this->expect($card)->toHaveScreenshot('card.png', $options));

        $this->card('#6366f1');
        $this->expect($card)->toHaveScreenshot('card.png', $options);

        $this->expectException(AssertionException::class);
        $this->expect($card)->toHaveScreenshot('card.png', new ToHaveScreenshotOptions(threshold: 0.1, mask: [$this->page->locator('.time')]));
    }

    public function testSsimComparatorIsAvailable(): void
    {
        $this->card('#818cf8');
        $card = $this->page->locator('#card');
        $options = new ToHaveScreenshotOptions(comparator: 'ssim-cie94', mask: [$this->page->locator('.time')]);
        $this->record(fn () => $this->expect($card)->toHaveScreenshot('card.png', $options));

        $this->card('#6366f1');

        $this->expectException(AssertionException::class);
        $this->expect($card)->toHaveScreenshot('card.png', $options);
    }

    public function testChangedModeAcceptsTheNewScreenshot(): void
    {
        $this->card('#818cf8', 'Hello');
        $card = $this->page->locator('#card');
        $options = new ToHaveScreenshotOptions(mask: [$this->page->locator('.time')]);
        $this->record(fn () => $this->expect($card)->toHaveScreenshot('card.png', $options));
        $before = file_get_contents($this->dir.'/card-'.$this->variant().'.png');

        $this->card('#818cf8', 'Hello world');
        $_SERVER[ScreenshotExpectation::UPDATE_ENV] = 'changed';
        $this->expect($card)->toHaveScreenshot('card.png', $options);
        unset($_SERVER[ScreenshotExpectation::UPDATE_ENV]);

        $this->assertNotSame($before, file_get_contents($this->dir.'/card-'.$this->variant().'.png'));
        $this->expect($card)->toHaveScreenshot('card.png', $options);
    }

    public function testWebpBaselineIsLosslessAndComparedAsWebp(): void
    {
        $this->card('#818cf8', 'Hello');
        $card = $this->page->locator('#card');
        $options = new ToHaveScreenshotOptions(mask: [$this->page->locator('.time')]);
        $this->record(fn () => $this->expect($card)->toHaveScreenshot('card.webp', $options));

        $baseline = (string) file_get_contents($this->dir.'/card-'.$this->variant().'.webp');
        $this->assertSame('WEBP', substr($baseline, 8, 4));

        $this->card('#818cf8', 'Hello');
        $this->expect($card)->toHaveScreenshot('card.webp', $options);

        $this->card('#818cf8', 'Hello world');
        try {
            $this->expect($card)->toHaveScreenshot('card.webp', $options);
            $this->fail('A changed element must fail the assertion.');
        } catch (AssertionException) {
        }

        $out = $this->testOutputDirectory().'-chromium/card';
        $this->assertSame('WEBP', substr((string) file_get_contents($out.'-actual.webp'), 8, 4));
        $this->assertSame("\x89PNG", substr((string) file_get_contents($out.'-diff.png'), 0, 4));
    }

    public function testFullPageCapturesBeyondTheViewport(): void
    {
        $this->page->setViewportSize(800, 600);
        $this->page->setContent('<body style="margin:0"><div style="height:2000px;background:linear-gradient(#818cf8,#f472b6)"></div></body>');

        $this->record(fn () => $this->expect($this->page)->toHaveScreenshot('full.png', new ToHaveScreenshotOptions(fullPage: true)));

        $size = getimagesize($this->dir.'/full-'.$this->variant().'.png');
        $this->assertIsArray($size);
        $this->assertSame([800, 2000], [$size[0], $size[1]]);

        $this->expect($this->page)->toHaveScreenshot('full.png', new ToHaveScreenshotOptions(fullPage: true));
    }

    public function testClipCapturesARegion(): void
    {
        $this->card('#818cf8');
        $clip = new ToHaveScreenshotOptions(clip: ['x' => 10, 'y' => 20, 'width' => 120, 'height' => 40]);

        $this->record(fn () => $this->expect($this->page)->toHaveScreenshot('region.png', $clip));

        $size = getimagesize($this->dir.'/region-'.$this->variant().'.png');
        $this->assertIsArray($size);
        $this->assertSame([120, 40], [$size[0], $size[1]]);
    }

    public function testMaskReachesInsideAnIframe(): void
    {
        $frame = fn (string $time): string => sprintf(
            '<body style="margin:0"><iframe id="preview" style="width:300px;height:80px;border:0" srcdoc="%s"></iframe></body>',
            htmlspecialchars(sprintf('<body style="margin:0;font:16px sans-serif"><p>Updated <span class="time">%s</span></p></body>', $time)),
        );
        $options = fn (): ToHaveScreenshotOptions => new ToHaveScreenshotOptions(
            mask: [$this->page->frameLocator('#preview')->locator('.time')],
        );

        $this->page->setContent($frame('10:00:00'));
        $this->page->frameLocator('#preview')->locator('.time')->waitFor();
        $this->record(fn () => $this->expect($this->page->locator('#preview'))->toHaveScreenshot('frame.png', $options()));

        $this->page->setContent($frame('23:59:59'));
        $this->page->frameLocator('#preview')->locator('.time')->waitFor();
        $this->expect($this->page->locator('#preview'))->toHaveScreenshot('frame.png', $options());

        $this->expectException(AssertionException::class);
        $this->expect($this->page->locator('#preview'))->toHaveScreenshot('frame.png');
    }

    public function testAllModeRewritesEvenAMatchingBaseline(): void
    {
        $this->card('#818cf8');
        $card = $this->page->locator('#card');
        $options = new ToHaveScreenshotOptions(mask: [$this->page->locator('.time')]);
        $this->record(fn () => $this->expect($card)->toHaveScreenshot('card.png', $options));
        $baseline = $this->dir.'/card-'.$this->variant().'.png';
        file_put_contents($baseline, (string) file_get_contents($baseline)."\0");

        $_SERVER[ScreenshotExpectation::UPDATE_ENV] = 'all';
        $this->expect($card)->toHaveScreenshot('card.png', $options);

        $this->assertStringEndsNotWith("\0", (string) file_get_contents($baseline));
    }

    public function testNoneModeNeverWritesABaseline(): void
    {
        $this->card('#818cf8');
        $_SERVER[ScreenshotExpectation::UPDATE_ENV] = 'none';

        try {
            $this->expect($this->page->locator('#card'))->toHaveScreenshot('card.png');
            $this->fail('A missing baseline must fail in none mode.');
        } catch (AssertionException $e) {
            $this->assertStringContainsString('PLAYWRIGHT_UPDATE_SNAPSHOTS=missing', $e->getMessage());
        }

        $this->assertFileDoesNotExist($this->dir.'/card-'.$this->variant().'.png');
    }

    public function testFullPageIsRejectedOnAnElement(): void
    {
        $this->card('#818cf8');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The fullPage and clip options apply to a page screenshot');

        $this->expect($this->page->locator('#card'))->toHaveScreenshot('card.png', new ToHaveScreenshotOptions(fullPage: true));
    }

    public function testCannotBeNegated(): void
    {
        $this->card('#818cf8');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('toHaveScreenshot() cannot be negated.');

        $this->expect($this->page)->not()->toHaveScreenshot('page.png');
    }

    private function card(string $color, string $title = 'Hello'): void
    {
        $this->page->setContent(sprintf(<<<'HTML'
            <body style="margin:0;font:16px sans-serif">
              <div id="card" style="width:300px;padding:24px;background:%s;border-radius:12px">
                <h1>%s</h1>
                <p class="time">%s</p>
                <div style="width:20px;height:20px;background:red;animation:spin 1s linear infinite"></div>
              </div>
              <style>@keyframes spin { to { transform: rotate(360deg) } }</style>
            </body>
            HTML, $color, $title, microtime()));
    }

    /**
     * Runs a first assertion that records the baseline, which fails by design.
     */
    private function record(callable $assertion): void
    {
        try {
            $assertion();
        } catch (AssertionException $e) {
            $this->assertStringContainsString('writing actual', $e->getMessage());
        }
    }

    /**
     * The functional suite runs Chromium.
     */
    private function variant(): string
    {
        return 'chromium-'.('Windows' === \PHP_OS_FAMILY ? 'win32' : strtolower(\PHP_OS_FAMILY));
    }

    private function remove(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') ?: [] as $file) {
            is_dir($file) ? $this->remove($file) : unlink($file);
        }
        rmdir($dir);
    }
}
