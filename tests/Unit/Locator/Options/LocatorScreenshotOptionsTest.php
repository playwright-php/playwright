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
use Playwright\Locator\Options\LocatorScreenshotOptions;

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
            'mask' => ['selector1', 'selector2'],
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
        $this->assertSame(['selector1', 'selector2'], $result['mask']);
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
}
