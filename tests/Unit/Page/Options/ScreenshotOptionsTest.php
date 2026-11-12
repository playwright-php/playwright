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

namespace Playwright\Tests\Unit\Page\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\Locator;
use Playwright\Page\Options\ScreenshotOptions;
use Playwright\Transport\TransportInterface;

#[CoversClass(ScreenshotOptions::class)]
final class ScreenshotOptionsTest extends TestCase
{
    public function testItCreatesFromObject(): void
    {
        $options = new ScreenshotOptions(fullPage: true);
        $this->assertSame($options, ScreenshotOptions::from($options));
    }

    public function testItCreatesFromArray(): void
    {
        $options = ScreenshotOptions::from(['fullPage' => true, 'type' => 'jpeg']);
        $this->assertTrue($options->fullPage);
        $this->assertSame('jpeg', $options->type);
    }

    public function testItThrowsExceptionForInvalidInput(): void
    {
        $this->expectException(\TypeError::class);

        ScreenshotOptions::from('invalid');
    }

    public function testToReturnArray(): void
    {
        $options = new ScreenshotOptions(
            path: 'screenshot.png',
            type: 'png',
            quality: 80,
            fullPage: true,
            clip: ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 100],
            omitBackground: true,
            timeout: 5000.0,
            animations: 'disabled',
            caret: 'hide',
            scale: 'css',
            mask: [],
            maskColor: '#000000'
        );

        $expected = [
            'path' => 'screenshot.png',
            'type' => 'png',
            'quality' => 80,
            'fullPage' => true,
            'clip' => ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 100],
            'omitBackground' => true,
            'timeout' => 5000.0,
            'animations' => 'disabled',
            'caret' => 'hide',
            'scale' => 'css',
            'mask' => [],
            'maskColor' => '#000000',
        ];

        $this->assertSame($expected, $options->toArray());
    }

    public function testItSendsMaskLocatorsAsSelectors(): void
    {
        $transport = $this->createStub(TransportInterface::class);
        $options = ScreenshotOptions::from(['mask' => [
            new Locator($transport, 'page1', '.ad'),
            new Locator($transport, 'page1', '.date', 'iframe#preview'),
        ]]);

        $this->assertSame([['selector' => '.ad'], ['selector' => '.date', 'frameSelector' => 'iframe#preview']], $options->toArray()['mask']);
    }

    public function testItRejectsMaskEntriesThatAreNotLocators(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The mask option expects Playwright\Locator\LocatorInterface instances, string given at index 0.');

        ScreenshotOptions::from(['mask' => ['.ad']]);
    }
}
