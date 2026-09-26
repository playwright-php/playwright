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

namespace Playwright\Tests\Unit\Assertions\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Assertions\Failure\AssertionException;
use Playwright\Assertions\Internal\ScreenshotExpectation;
use Playwright\Assertions\Options\ToHaveScreenshotOptions;
use Playwright\Screenshot\ScreenshotComparison;

#[CoversClass(ScreenshotExpectation::class)]
final class ScreenshotExpectationTest extends TestCase
{
    private string $dir;

    /** @var list<array{expected: ?string, options: array<string, mixed>}> */
    private array $calls = [];

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/pw_screenshot_expectation_'.uniqid();
        mkdir($this->dir);
        $this->calls = [];
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->dir);
    }

    public function testMissingBaselineIsWrittenAndFailsByDefault(): void
    {
        $path = $this->dir.'/snapshots/card.png';

        try {
            $this->expectation(new ScreenshotComparison(actual: 'stable'))->assert($path, new ToHaveScreenshotOptions(), 5000);
            $this->fail('A missing baseline must fail the assertion.');
        } catch (AssertionException $e) {
            $this->assertStringContainsString("doesn't exist at {$path}, writing actual", $e->getMessage());
        }

        $this->assertSame('stable', file_get_contents($path));
        $this->assertNull($this->calls[0]['expected']);
    }

    #[DataProvider('provideUpdatingModes')]
    public function testMissingBaselineIsWrittenAndPassesWhenUpdating(string $mode): void
    {
        $path = $this->dir.'/card.png';

        $this->expectation(new ScreenshotComparison(actual: 'stable'), $mode)->assert($path, new ToHaveScreenshotOptions(), 5000);

        $this->assertSame('stable', file_get_contents($path));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUpdatingModes(): iterable
    {
        yield 'changed' => ['changed'];
        yield 'all' => ['all'];
    }

    public function testMissingBaselineIsNotWrittenInNoneMode(): void
    {
        $path = $this->dir.'/card.png';

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('Run with PLAYWRIGHT_UPDATE_SNAPSHOTS=missing to write it.');

        try {
            $this->expectation(new ScreenshotComparison(actual: 'stable'), 'none')->assert($path, new ToHaveScreenshotOptions(), 5000);
        } finally {
            $this->assertFileDoesNotExist($path);
        }
    }

    public function testUnstablePageFailsWithoutWritingABaseline(): void
    {
        $path = $this->dir.'/card.png';
        $unstable = new ScreenshotComparison(actual: 'last', errorMessage: 'Failed to take two consecutive stable screenshots.', timedOut: true);

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('Failed to take a stable screenshot of the page: Failed to take two consecutive stable screenshots.');

        try {
            $this->expectation($unstable)->assert($path, new ToHaveScreenshotOptions(), 5000);
        } finally {
            $this->assertFileDoesNotExist($path);
        }
    }

    public function testMatchingScreenshotPassesAndSendsTheBaseline(): void
    {
        $path = $this->baseline('baseline');

        $this->expectation(new ScreenshotComparison(actual: null))->assert($path, new ToHaveScreenshotOptions(threshold: 0.1), 1234);

        $this->assertSame('baseline', $this->calls[0]['expected']);
        $this->assertSame(0.1, $this->calls[0]['options']['threshold']);
        $this->assertSame(1234, $this->calls[0]['options']['timeout']);
        $this->assertSame('png', $this->calls[0]['options']['type']);
    }

    public function testOptionTimeoutWinsOverTheAssertionTimeout(): void
    {
        $path = $this->baseline('baseline');

        $this->expectation(new ScreenshotComparison(actual: null))->assert($path, new ToHaveScreenshotOptions(timeoutMs: 42), 5000);

        $this->assertSame(42, $this->calls[0]['options']['timeout']);
    }

    public function testMismatchKeepsExpectedActualAndDiff(): void
    {
        $path = $this->baseline('baseline');
        $mismatch = new ScreenshotComparison(actual: 'actual', diff: 'diff', errorMessage: '120 pixels (ratio 0.02 of all image pixels) are different.');

        try {
            $this->expectation($mismatch)->assert($path, new ToHaveScreenshotOptions(), 5000);
            $this->fail('A mismatch must fail the assertion.');
        } catch (AssertionException $e) {
            $this->assertStringContainsString("Screenshot of the page does not match {$path}.", $e->getMessage());
            $this->assertStringContainsString('120 pixels (ratio 0.02 of all image pixels) are different.', $e->getMessage());
            $this->assertStringContainsString('PLAYWRIGHT_UPDATE_SNAPSHOTS=changed', $e->getMessage());
            $this->assertSame($this->artifact('card-actual.png'), $e->actual);
        }

        $this->assertSame('baseline', file_get_contents($this->artifact('card-expected.png')));
        $this->assertSame('actual', file_get_contents($this->artifact('card-actual.png')));
        $this->assertSame('diff', file_get_contents($this->artifact('card-diff.png')));
        $this->assertSame('baseline', file_get_contents($path));
    }

    public function testMatchRemovesTheImagesOfAnEarlierFailure(): void
    {
        $path = $this->baseline('baseline');
        $this->staleArtifacts();

        $this->expectation(new ScreenshotComparison(actual: null))->assert($path, new ToHaveScreenshotOptions(), 5000);

        $this->assertDirectoryDoesNotExist($this->dir.'/out');
    }

    /**
     * @param 'changed'|'all' $mode
     */
    #[DataProvider('provideUpdatingModes')]
    public function testRecordingRemovesTheImagesOfAnEarlierFailure(string $mode): void
    {
        $path = $this->baseline('baseline');
        $this->staleArtifacts();

        $this->expectation(new ScreenshotComparison(actual: 'actual', errorMessage: 'changed' === $mode ? 'different' : null), $mode)
            ->assert($path, new ToHaveScreenshotOptions(), 5000);

        $this->assertDirectoryDoesNotExist($this->dir.'/out');
    }

    public function testFailureKeepsTheImagesOfOtherBaselines(): void
    {
        $path = $this->baseline('baseline');
        mkdir($this->dir.'/out');
        file_put_contents($this->dir.'/out/other-actual.png', 'other');

        $this->expectation(new ScreenshotComparison(actual: null))->assert($path, new ToHaveScreenshotOptions(), 5000);

        $this->assertFileExists($this->dir.'/out/other-actual.png');
    }

    #[DataProvider('provideCollidingBaselineNames')]
    public function testArtifactsBelongOnlyToTheirBaseline(string $firstName, string $secondName, string $cleanupMode): void
    {
        $paths = [$this->dir.'/'.$firstName, $this->dir.'/'.$secondName];
        $directories = [];

        foreach ($paths as $index => $path) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, 'baseline '.$index);

            try {
                $this->expectation(new ScreenshotComparison(actual: 'actual '.$index, diff: 'diff '.$index, errorMessage: 'different'))
                    ->assert($path, new ToHaveScreenshotOptions(), 5000);
                $this->fail('A mismatch must fail the assertion.');
            } catch (AssertionException $e) {
                $this->assertNotNull($e->actual);
                $directories[] = dirname($e->actual);
            }
        }

        $this->assertNotSame($directories[0], $directories[1]);
        foreach ($paths as $index => $path) {
            $extension = pathinfo($path, \PATHINFO_EXTENSION);
            $this->assertSame('baseline '.$index, file_get_contents($directories[$index].'/card-expected.'.$extension));
            $this->assertSame('actual '.$index, file_get_contents($directories[$index].'/card-actual.'.$extension));
            $this->assertSame('diff '.$index, file_get_contents($directories[$index].'/card-diff.png'));
        }

        $result = match ($cleanupMode) {
            'changed' => new ScreenshotComparison(actual: 'updated', errorMessage: 'different'),
            'all' => new ScreenshotComparison(actual: 'updated'),
            default => new ScreenshotComparison(actual: null),
        };
        $this->expectation($result, $cleanupMode)->assert($paths[0], new ToHaveScreenshotOptions(), 5000);

        $this->assertDirectoryDoesNotExist($directories[0]);
        $this->assertSame('diff 1', file_get_contents($directories[1].'/card-diff.png'));

        $this->expectation(new ScreenshotComparison(actual: null))->assert($paths[1], new ToHaveScreenshotOptions(), 5000);

        $this->assertDirectoryDoesNotExist($this->dir.'/out');
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideCollidingBaselineNames(): iterable
    {
        foreach (['none', 'changed', 'all'] as $mode) {
            yield 'different directories, '.$mode => ['a/card.png', 'b/card.png', $mode];
            yield 'different formats, '.$mode => ['card.png', 'card.webp', $mode];
        }
    }

    public function testMatchingTheSameBaselineThroughAnotherPathCleansItsArtifacts(): void
    {
        $this->baseline('baseline');
        $this->staleArtifacts();

        $this->expectation(new ScreenshotComparison(actual: null))
            ->assert($this->dir.'/./card.png', new ToHaveScreenshotOptions(), 5000);

        $this->assertDirectoryDoesNotExist($this->dir.'/out');
    }

    public function testFailureImagesDropTheVariantOfTheBaselineName(): void
    {
        $path = $this->dir.'/card-chromium-linux.webp';
        file_put_contents($path, 'baseline');
        $mismatch = new ScreenshotComparison(actual: 'actual', diff: 'diff', errorMessage: 'different');

        try {
            $this->expectation($mismatch, variant: 'chromium-linux')->assert($path, new ToHaveScreenshotOptions(), 5000);
            $this->fail('A mismatch must fail the assertion.');
        } catch (AssertionException) {
        }

        $this->assertSame(['card-actual.webp', 'card-diff.png', 'card-expected.webp'], array_map('basename', glob($this->dir.'/out/*/*') ?: []));

        $this->expectation(new ScreenshotComparison(actual: null), variant: 'chromium-linux')->assert($path, new ToHaveScreenshotOptions(), 5000);

        $this->assertDirectoryDoesNotExist($this->dir.'/out');
    }

    public function testFailureImagesKeepANameThatDoesNotEndWithTheVariant(): void
    {
        $path = $this->baseline('baseline');

        try {
            $this->expectation(new ScreenshotComparison(actual: 'actual', errorMessage: 'different'), variant: 'chromium-linux')
                ->assert($path, new ToHaveScreenshotOptions(), 5000);
        } catch (AssertionException) {
        }

        $this->assertFileExists($this->artifact('card-actual.png'));
    }

    public function testMismatchUsesTheCustomMessage(): void
    {
        $path = $this->baseline('baseline');

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('The card changed.');

        $this->expectation(new ScreenshotComparison(actual: 'actual', errorMessage: 'different'))
            ->assert($path, new ToHaveScreenshotOptions(message: 'The card changed.'), 5000);
    }

    public function testChangedModeRewritesAMismatchingBaseline(): void
    {
        $path = $this->baseline('baseline');

        $this->expectation(new ScreenshotComparison(actual: 'actual', errorMessage: 'different'), 'changed')->assert($path, new ToHaveScreenshotOptions(), 5000);

        $this->assertSame('actual', file_get_contents($path));
    }

    public function testChangedModeKeepsAMatchingBaseline(): void
    {
        $path = $this->baseline('baseline');

        $this->expectation(new ScreenshotComparison(actual: null), 'changed')->assert($path, new ToHaveScreenshotOptions(), 5000);

        $this->assertSame('baseline', file_get_contents($path));
    }

    public function testAllModeRecordsWithoutComparing(): void
    {
        $path = $this->baseline('baseline');

        $this->expectation(new ScreenshotComparison(actual: 'stable'), 'all')->assert($path, new ToHaveScreenshotOptions(), 5000);

        $this->assertSame('stable', file_get_contents($path));
        $this->assertNull($this->calls[0]['expected']);
    }

    public function testAllModeNeverRecordsAnUnstableScreenshot(): void
    {
        $path = $this->baseline('baseline');

        $this->expectException(AssertionException::class);
        $this->expectExceptionMessage('Failed to take a stable screenshot');

        try {
            $this->expectation(new ScreenshotComparison(actual: 'moving', errorMessage: 'unstable', timedOut: true), 'all')
                ->assert($path, new ToHaveScreenshotOptions(), 5000);
        } finally {
            $this->assertSame('baseline', file_get_contents($path));
        }
    }

    public function testMismatchFailsInNoneMode(): void
    {
        $path = $this->baseline('baseline');

        $this->expectException(AssertionException::class);

        try {
            $this->expectation(new ScreenshotComparison(actual: 'actual', errorMessage: 'different'), 'none')->assert($path, new ToHaveScreenshotOptions(), 5000);
        } finally {
            $this->assertSame('baseline', file_get_contents($path));
        }
    }

    public function testTimedOutMismatchNeverRewritesTheBaseline(): void
    {
        $path = $this->baseline('baseline');

        $this->expectException(AssertionException::class);

        try {
            $this->expectation(new ScreenshotComparison(actual: 'moving', errorMessage: 'unstable', timedOut: true), 'changed')
                ->assert($path, new ToHaveScreenshotOptions(), 5000);
        } finally {
            $this->assertSame('baseline', file_get_contents($path));
        }
    }

    public function testWebpBaselineRequestsWebpAndKeepsWebpArtifacts(): void
    {
        $path = $this->dir.'/card.webp';
        file_put_contents($path, 'baseline');

        try {
            $this->expectation(new ScreenshotComparison(actual: 'actual', diff: 'diff', errorMessage: 'different'))->assert($path, new ToHaveScreenshotOptions(), 5000);
            $this->fail('A mismatch must fail the assertion.');
        } catch (AssertionException) {
        }

        $this->assertSame('webp', $this->calls[0]['options']['type']);
        $this->assertFileExists($this->artifact('card-expected.webp'));
        $this->assertFileExists($this->artifact('card-actual.webp'));
        $this->assertFileExists($this->artifact('card-diff.png'));
    }

    public function testJpegBaselineIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a .png or .webp file.');

        $this->expectation(new ScreenshotComparison(actual: 'stable'))->assert($this->dir.'/card.jpg', new ToHaveScreenshotOptions(), 5000);
    }

    public function testUnstablePageWithABaselineKeepsTheLastScreenshot(): void
    {
        $path = $this->baseline('baseline');

        try {
            $this->expectation(new ScreenshotComparison(actual: 'moving', errorMessage: 'Failed to take two consecutive stable screenshots.', timedOut: true))
                ->assert($path, new ToHaveScreenshotOptions(), 5000);
            $this->fail('An unstable page must fail the assertion.');
        } catch (AssertionException $e) {
            $this->assertStringContainsString('Failed to take two consecutive stable screenshots.', $e->getMessage());
        }

        $this->assertSame('baseline', file_get_contents($path));
        $this->assertSame('baseline', file_get_contents($this->artifact('card-expected.png')));
        $this->assertSame('moving', file_get_contents($this->artifact('card-actual.png')));
    }

    public function testUnreadableBaselineIsReported(): void
    {
        if (0 === posix_geteuid()) {
            $this->markTestSkipped('Root reads files whatever their permissions.');
        }

        $path = $this->baseline('baseline');
        chmod($path, 0o000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Unable to read the snapshot "%s".', $path));

        try {
            $this->expectation(new ScreenshotComparison(actual: null))->assert($path, new ToHaveScreenshotOptions(), 5000);
        } finally {
            chmod($path, 0o644);
        }
    }

    public function testUncreatableOutputDirectoryIsReported(): void
    {
        $path = $this->baseline('baseline');
        file_put_contents($this->dir.'/file', 'not a directory');

        $expectation = new ScreenshotExpectation(
            static fn (): ScreenshotComparison => new ScreenshotComparison(actual: 'actual', errorMessage: 'different'),
            'page',
            'none',
            $this->dir.'/file/out',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Directory "%s/file/out/', $this->dir));

        $expectation->assert($path, new ToHaveScreenshotOptions(), 5000);
    }

    public function testUnwritableBaselineIsReported(): void
    {
        $path = $this->dir.'/card.png';
        mkdir($path);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('Unable to write "%s".', $path));

        $this->expectation(new ScreenshotComparison(actual: 'stable'), 'changed')->assert($path, new ToHaveScreenshotOptions(), 5000);
    }

    public function testUnknownUpdateModeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PLAYWRIGHT_UPDATE_SNAPSHOTS must be one of "none", "missing", "changed", "all", "yes" given.');

        $this->expectation(new ScreenshotComparison(actual: 'stable'), 'yes')->assert($this->dir.'/card.png', new ToHaveScreenshotOptions(), 5000);
    }

    private function artifact(string $name): string
    {
        $files = glob($this->dir.'/out/*/'.$name) ?: [];
        $this->assertCount(1, $files);

        return $files[0];
    }

    private function staleArtifacts(): void
    {
        try {
            $this->expectation(new ScreenshotComparison(actual: 'stale', diff: 'stale', errorMessage: 'different'))
                ->assert($this->dir.'/card.png', new ToHaveScreenshotOptions(), 5000);
            $this->fail('A mismatch must fail the assertion.');
        } catch (AssertionException) {
        }
    }

    private function baseline(string $contents): string
    {
        $path = $this->dir.'/card.png';
        file_put_contents($path, $contents);

        return $path;
    }

    private function expectation(ScreenshotComparison $result, ?string $mode = null, ?string $variant = null): ScreenshotExpectation
    {
        return new ScreenshotExpectation(
            function (?string $expected, array $options) use ($result): ScreenshotComparison {
                $this->calls[] = ['expected' => $expected, 'options' => $options];

                return $result;
            },
            'page',
            $mode,
            $this->dir.'/out',
            $variant,
        );
    }
}
