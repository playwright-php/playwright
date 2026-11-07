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

namespace Playwright\Assertions\Internal;

use Playwright\Assertions\Failure\AssertionException;
use Playwright\Assertions\Options\ToHaveScreenshotOptions;
use Playwright\Screenshot\ScreenshotComparison;

/**
 * Compares a stable screenshot against a baseline file, the way.
 *
 * @playwright/test does it: the baseline is written on first run, and
 * the expected, actual and diff images are kept when the comparison fails.
 *
 * The update mode comes from PLAYWRIGHT_UPDATE_SNAPSHOTS, with the values of
 * the --update-snapshots flag: "none", "missing" (default), "changed", "all".
 *
 * @internal
 */
final class ScreenshotExpectation
{
    public const UPDATE_ENV = 'PLAYWRIGHT_UPDATE_SNAPSHOTS';

    private const UPDATE_MODES = ['none', 'missing', 'changed', 'all'];

    /**
     * @param \Closure(?string, array<string, mixed>): ScreenshotComparison $compare
     */
    public function __construct(
        private readonly \Closure $compare,
        private readonly string $subject,
        private readonly ?string $updateMode = null,
        private readonly ?string $outputDir = null,
        private readonly ?string $variant = null,
    ) {
    }

    public function assert(string $path, ToHaveScreenshotOptions $options, int $timeoutMs): void
    {
        $mode = $this->updateMode();
        $payload = $options->toArray() + ['timeout' => $options->timeoutMs ?? $timeoutMs, 'type' => $this->imageType($path)];

        $expected = is_file($path) ? @file_get_contents($path) : null;
        if (false === $expected) {
            throw new \RuntimeException(sprintf('Unable to read the snapshot "%s".', $path));
        }

        // An empty file would reach Playwright as "no baseline" and match
        // anything: it only passes once recorded again.
        if ('' === $expected && 'changed' !== $mode && 'all' !== $mode) {
            throw new AssertionException($options->message ?? sprintf('The snapshot at %s is empty. Run with %s=changed to record it again.', $path, self::UPDATE_ENV));
        }

        // "all" records every screenshot, like --update-snapshots=all: no
        // comparison, Playwright returns no image when a comparison matches.
        // "changed" records an empty baseline the same way.
        if (null === $expected || '' === $expected || 'all' === $mode) {
            $this->record($path, $mode, null === $expected, ($this->compare)(null, $payload), $options);

            return;
        }

        $result = ($this->compare)($expected, $payload);

        if ($result->matches()) {
            $this->removeArtifacts($path);

            return;
        }

        if ('changed' === $mode && null !== $result->actual && !$result->timedOut) {
            $this->write($path, $result->actual);
            $this->removeArtifacts($path);

            return;
        }

        $artifacts = $this->writeArtifacts($path, $expected, $result);

        throw new AssertionException($this->failureMessage($options, $result, $path, $artifacts), actual: $artifacts['actual'] ?? null, expected: $path);
    }

    private function record(string $path, string $mode, bool $missing, ScreenshotComparison $result, ToHaveScreenshotOptions $options): void
    {
        if (null === $result->actual || !$result->matches()) {
            throw new AssertionException($options->message ?? sprintf('Failed to take a stable screenshot of the %s: %s', $this->subject, $result->errorMessage ?? 'no screenshot returned'));
        }

        if ($missing && 'none' === $mode) {
            throw new AssertionException($options->message ?? sprintf('A snapshot doesn\'t exist at %s. Run with %s=missing to write it.', $path, self::UPDATE_ENV));
        }

        $this->write($path, $result->actual);
        $this->removeArtifacts($path);

        if ($missing && 'missing' === $mode) {
            throw new AssertionException($options->message ?? sprintf('A snapshot doesn\'t exist at %s, writing actual.', $path));
        }
    }

    /**
     * Images of an earlier failure would read as a current one: they go as
     * soon as the baseline matches or is recorded again.
     */
    private function removeArtifacts(string $path): void
    {
        $stem = $this->artifactStem($path);
        $extension = '.'.$this->imageType($path);

        foreach ([$stem.'-expected'.$extension, $stem.'-actual'.$extension, $stem.'-diff.png'] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        foreach ([dirname($stem), $this->outputDir()] as $directory) {
            if (is_dir($directory) && [] === array_diff(scandir($directory) ?: [], ['.', '..'])) {
                rmdir($directory);
            }
        }
    }

    /**
     * @return array<'expected'|'actual'|'diff', string>
     */
    private function writeArtifacts(string $path, string $expected, ScreenshotComparison $result): array
    {
        $this->removeArtifacts($path);

        $stem = $this->artifactStem($path);
        $extension = '.'.$this->imageType($path);

        $artifacts = ['expected' => $stem.'-expected'.$extension];
        $this->write($artifacts['expected'], $expected);

        if (null !== $result->actual) {
            $artifacts['actual'] = $stem.'-actual'.$extension;
            $this->write($artifacts['actual'], $result->actual);
        }
        if (null !== $result->diff) {
            $artifacts['diff'] = $stem.'-diff.png';
            $this->write($artifacts['diff'], $result->diff);
        }

        return $artifacts;
    }

    /**
     * @param array<'expected'|'actual'|'diff', string> $artifacts
     */
    private function failureMessage(ToHaveScreenshotOptions $options, ScreenshotComparison $result, string $path, array $artifacts): string
    {
        $message = $options->message ?? sprintf('Screenshot of the %s does not match %s.', $this->subject, $path);

        $lines = [$message, (string) $result->errorMessage];
        foreach ($artifacts as $kind => $file) {
            $lines[] = sprintf('  %-9s %s', $kind.':', $file);
        }
        $lines[] = sprintf('Run with %s=changed to accept the new screenshot.', self::UPDATE_ENV);

        return implode("\n", $lines);
    }

    /**
     * The baseline extension picks the image format. WebP is lossless here,
     * smaller and faster to compare; JPEG is rejected, its compression noise
     * would fail every comparison.
     *
     * @return 'png'|'webp'
     */
    private function imageType(string $path): string
    {
        return match (strtolower(pathinfo($path, \PATHINFO_EXTENSION))) {
            'png' => 'png',
            'webp' => 'webp',
            default => throw new \InvalidArgumentException(sprintf('Snapshot "%s" must be a .png or .webp file.', $path)),
        };
    }

    private function updateMode(): string
    {
        $mode = $this->updateMode ?? $_SERVER[self::UPDATE_ENV] ?? getenv(self::UPDATE_ENV);
        if (!is_string($mode) || '' === $mode) {
            return 'missing';
        }

        if (!in_array($mode, self::UPDATE_MODES, true)) {
            throw new \InvalidArgumentException(sprintf('%s must be one of "%s", "%s" given.', self::UPDATE_ENV, implode('", "', self::UPDATE_MODES), $mode));
        }

        return $mode;
    }

    /**
     * Failure images drop the variant of the baseline name, which their
     * directory already carries: "card-chromium-linux.png" fails as
     * "card-actual.png", as in Playwright JS. Each baseline gets its own
     * directory so matching one cannot erase another baseline's failure.
     */
    private function artifactStem(string $path): string
    {
        $stem = pathinfo($path, \PATHINFO_FILENAME);
        if (null !== $this->variant && '' !== $this->variant && str_ends_with($stem, '-'.$this->variant)) {
            $stem = substr($stem, 0, -\strlen($this->variant) - 1);
        }

        $identity = substr(hash('sha256', realpath($path) ?: $path), 0, 12);

        return $this->outputDir().'/'.$stem.'-'.$identity.'/'.$stem;
    }

    private function outputDir(): string
    {
        return $this->outputDir ?? getcwd().'/test-failures/snapshots';
    }

    private function write(string $file, string $contents): void
    {
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created.', $dir));
        }

        if (false === @file_put_contents($file, $contents)) {
            throw new \RuntimeException(sprintf('Unable to write "%s".', $file));
        }
    }
}
