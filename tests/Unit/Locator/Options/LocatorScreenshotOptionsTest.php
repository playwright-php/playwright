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

namespace Playwright\Tests\Unit\Locator\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\Locator;
use Playwright\Locator\Options\LocatorScreenshotOptions;
use Playwright\Transport\TransportInterface;

#[CoversClass(LocatorScreenshotOptions::class)]
final class LocatorScreenshotOptionsTest extends TestCase
{
    public function testToArrayIncludesAllProperties(): void
    {
        $options = LocatorScreenshotOptions::from([
            'path' => '/path/to/screenshot.png',
            'type' => 'png',
            'quality' => 80,
            'omitBackground' => true,
            'timeout' => 8000.0,
            'animations' => 'disabled',
            'caret' => 'hide',
            'scale' => 'css',
            'mask' => [$this->locator('.ad'), $this->locator('.date', 'iframe#preview')],
            'maskColor' => '#00FF00',
            'style' => 'background: red;',
        ]);

        $result = $options->toArray();

        $this->assertSame('/path/to/screenshot.png', $result['path']);
        $this->assertSame('png', $result['type']);
        $this->assertSame(80, $result['quality']);
        $this->assertTrue($result['omitBackground']);
        $this->assertSame(8000.0, $result['timeout']);
        $this->assertSame('disabled', $result['animations']);
        $this->assertSame('hide', $result['caret']);
        $this->assertSame('css', $result['scale']);
        $this->assertSame([['selector' => '.ad'], ['selector' => '.date', 'frameSelector' => 'iframe#preview']], $result['mask']);
        $this->assertSame('#00FF00', $result['maskColor']);
        $this->assertSame('background: red;', $result['style']);
    }

    public function testToArrayExcludesNullProperties(): void
    {
        $options = LocatorScreenshotOptions::from([
            'path' => '/path/to/image.png',
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayNotHasKey('type', $result);
        $this->assertArrayNotHasKey('quality', $result);
        $this->assertArrayNotHasKey('omitBackground', $result);
        $this->assertArrayNotHasKey('timeout', $result);
        $this->assertArrayNotHasKey('animations', $result);
        $this->assertArrayNotHasKey('caret', $result);
        $this->assertArrayNotHasKey('scale', $result);
        $this->assertArrayNotHasKey('mask', $result);
        $this->assertArrayNotHasKey('maskColor', $result);
        $this->assertArrayNotHasKey('style', $result);
    }

    public function testFromAcceptsSelfInstance(): void
    {
        $original = new LocatorScreenshotOptions(path: '/test.png');
        $result = LocatorScreenshotOptions::from($original);

        $this->assertSame($original, $result);
    }

    public function testConstructorRejectsMaskEntriesThatAreNotLocators(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The mask option expects Playwright\Locator\LocatorInterface instances, string given at index 0.');

        new LocatorScreenshotOptions(mask: ['.ad']); // @phpstan-ignore argument.type
    }

    public function testFromRejectsMaskEntriesThatAreNotLocators(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('string given at index 0.');

        LocatorScreenshotOptions::from(['mask' => ['.ad']]);
    }

    private function locator(string $selector, ?string $frameSelector = null): Locator
    {
        return new Locator($this->createStub(TransportInterface::class), 'page1', $selector, $frameSelector);
    }
}
