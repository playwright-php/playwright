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

namespace Playwright\Tests\Unit\Screenshot;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\Locator;
use Playwright\Locator\LocatorInterface;
use Playwright\Screenshot\ScreenshotMask;
use Playwright\Transport\TransportInterface;

#[CoversClass(ScreenshotMask::class)]
final class ScreenshotMaskTest extends TestCase
{
    public function testLocatorsBecomeSelectors(): void
    {
        $other = $this->createStub(LocatorInterface::class);
        $other->method('getSelector')->willReturn('.timestamp');

        $mask = [
            new Locator($this->createStub(TransportInterface::class), 'page1', '.ad'),
            new Locator($this->createStub(TransportInterface::class), 'page1', '.date', 'iframe#preview'),
            $other,
        ];

        $this->assertSame([
            ['selector' => '.ad'],
            ['selector' => '.date', 'frameSelector' => 'iframe#preview'],
            ['selector' => '.timestamp'],
        ], ScreenshotMask::toArray($mask));
    }

    public function testLocatorsAreAccepted(): void
    {
        ScreenshotMask::assertLocators([$this->createStub(LocatorInterface::class)]);

        $this->addToAssertionCount(1);
    }

    public function testOtherEntriesAreRejectedWithTheirIndex(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The mask option expects Playwright\Locator\LocatorInterface instances, array given at index ad.');

        ScreenshotMask::assertLocators(['ad' => ['selector' => '.ad']]);
    }
}
