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

namespace Playwright\Tests\Unit\Assertions\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Assertions\Options\ToHaveScreenshotOptions;
use Playwright\Locator\Locator;
use Playwright\Locator\LocatorInterface;
use Playwright\Transport\TransportInterface;

#[CoversClass(ToHaveScreenshotOptions::class)]
final class ToHaveScreenshotOptionsTest extends TestCase
{
    public function testDefaultsMatchPlaywrightTest(): void
    {
        $this->assertSame([
            'threshold' => 0.2,
            'comparator' => 'pixelmatch',
            'maskColor' => '#FF00FF',
            'fullPage' => false,
            'omitBackground' => false,
            'animations' => 'disabled',
            'caret' => 'hide',
            'scale' => 'css',
        ], (new ToHaveScreenshotOptions())->toArray());
    }

    public function testToleranceHasNoDefault(): void
    {
        $options = new ToHaveScreenshotOptions();

        $this->assertNull($options->maxDiffPixels);
        $this->assertNull($options->maxDiffPixelRatio);
        $this->assertNull($options->timeoutMs);
    }

    public function testSendsTheValuesSet(): void
    {
        $options = new ToHaveScreenshotOptions(
            threshold: 0.1,
            maxDiffPixels: 10,
            maxDiffPixelRatio: 0.01,
            comparator: ToHaveScreenshotOptions::COMPARATOR_SSIM_CIE94,
            style: '.ad { display: none }',
            fullPage: true,
            clip: ['x' => 0, 'y' => 0, 'width' => 320, 'height' => 200],
            omitBackground: true,
            animations: 'allow',
            caret: 'initial',
            scale: 'device',
        );

        $this->assertSame([
            'threshold' => 0.1,
            'maxDiffPixels' => 10,
            'maxDiffPixelRatio' => 0.01,
            'comparator' => 'ssim-cie94',
            'maskColor' => '#FF00FF',
            'style' => '.ad { display: none }',
            'fullPage' => true,
            'clip' => ['x' => 0, 'y' => 0, 'width' => 320, 'height' => 200],
            'omitBackground' => true,
            'animations' => 'allow',
            'caret' => 'initial',
            'scale' => 'device',
        ], $options->toArray());
    }

    public function testTimeoutAndMessageStayOnThePhpSide(): void
    {
        $options = (new ToHaveScreenshotOptions(timeoutMs: 1000, message: 'The card changed.'))->toArray();

        $this->assertArrayNotHasKey('timeoutMs', $options);
        $this->assertArrayNotHasKey('timeout', $options);
        $this->assertArrayNotHasKey('message', $options);
    }

    public function testSerializesMaskLocatorsAsSelectors(): void
    {
        $locator = $this->createStub(LocatorInterface::class);
        $locator->method('getSelector')->willReturn('.timestamp');

        $options = new ToHaveScreenshotOptions(mask: [$locator], maskColor: 'black');

        $this->assertSame('black', $options->toArray()['maskColor']);
        $this->assertSame([['selector' => '.timestamp']], $options->toArray()['mask']);
    }

    public function testMaskLocatorsInAFrameKeepTheirFrame(): void
    {
        $locator = new Locator($this->createStub(TransportInterface::class), 'page1', '.date', 'iframe#preview');

        $options = new ToHaveScreenshotOptions(mask: [$locator]);

        $this->assertSame([['selector' => '.date', 'frameSelector' => 'iframe#preview']], $options->toArray()['mask']);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('provideInvalidOptions')]
    public function testRejectsInvalidOptions(array $arguments, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new ToHaveScreenshotOptions(...$arguments);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function provideInvalidOptions(): iterable
    {
        foreach (['NaN' => \NAN, 'positive infinity' => \INF, 'negative infinity' => -\INF] as $label => $value) {
            foreach (['threshold', 'maxDiffPixelRatio'] as $option) {
                yield $option.' '.$label => [[$option => $value], sprintf('The %s option must be between 0 and 1', $option)];
            }
            foreach (['x', 'y', 'width', 'height'] as $coordinate) {
                $clip = ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 10];
                $clip[$coordinate] = $value;

                yield 'clip '.$coordinate.' '.$label => [['clip' => $clip], sprintf('The clip option needs a finite "%s" value.', $coordinate)];
            }
        }

        yield 'threshold above 1' => [['threshold' => 2.0], 'The threshold option must be between 0 and 1, 2 given.'];
        yield 'negative threshold' => [['threshold' => -0.1], 'The threshold option must be between 0 and 1, -0.1 given.'];
        yield 'ratio above 1' => [['maxDiffPixelRatio' => 1.5], 'The maxDiffPixelRatio option must be between 0 and 1, 1.5 given.'];
        yield 'negative pixels' => [['maxDiffPixels' => -1], 'The maxDiffPixels option must be 0 or more, -1 given.'];
        yield 'negative timeout' => [['timeoutMs' => -1], 'The timeoutMs option must be 0 or more, -1 given.'];
        yield 'unknown comparator' => [['comparator' => 'ssim'], 'The comparator option must be one of "pixelmatch", "ssim-cie94", "ssim" given.'];
        yield 'unknown animations' => [['animations' => 'none'], 'The animations option must be one of "disabled", "allow", "none" given.'];
        yield 'unknown caret' => [['caret' => 'hidden'], 'The caret option must be one of "hide", "initial", "hidden" given.'];
        yield 'unknown scale' => [['scale' => 'px'], 'The scale option must be one of "css", "device", "px" given.'];
        yield 'empty mask color' => [['maskColor' => ' '], 'The maskColor option must be a CSS color, an empty string given.'];
        yield 'selector in mask' => [['mask' => ['.date']], 'The mask option expects Playwright\Locator\LocatorInterface instances, string given at index 0.'];
        yield 'clip without height' => [['clip' => ['x' => 0, 'y' => 0, 'width' => 10]], 'The clip option needs numeric "x", "y", "width" and "height" keys, "height" is missing or not a number.'];
        yield 'clip with a string' => [['clip' => ['x' => '0', 'y' => 0, 'width' => 10, 'height' => 10]], '"x" is missing or not a number.'];
        yield 'empty clip' => [['clip' => ['x' => 0, 'y' => 0, 'width' => 0, 'height' => 10]], 'The clip option needs a width and a height greater than 0.'];
        yield 'clip with an extra key' => [['clip' => ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 10, 'scale' => 2]], 'The clip option only accepts "x", "y", "width" and "height" keys, "x", "y", "width", "height", "scale" given.'];
    }
}
