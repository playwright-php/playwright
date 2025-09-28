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

namespace Playwright\Tests\Unit\Locator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Playwright\Exception\TimeoutException;
use Playwright\Locator\Locator;
use Playwright\Transport\TransportInterface;

#[CoversClass(Locator::class)]
final class LocatorAutoWaitTest extends TestCase
{
    private TransportInterface $transport;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
    }

    public function testClickWaitsForActionable(): void
    {
        $callCount = 0;
        $this->transport
            ->expects($this->exactly(3))
            ->method('send')
            ->willReturnCallback(function ($payload) use (&$callCount) {
                ++$callCount;

                if (1 === $callCount) {
                    $this->assertEquals('locator.isVisible', $payload['action']);

                    return ['value' => true];
                }

                if (2 === $callCount) {
                    $this->assertEquals('locator.isEnabled', $payload['action']);

                    return ['value' => true];
                }

                if (3 === $callCount) {
                    $this->assertEquals('locator.click', $payload['action']);

                    return [];
                }

                return [];
            });

        $locator = new Locator($this->transport, 'page1', '.button');

        $locator->click();
    }

    public function testWaitForVisibleSucceeds(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.isVisible' === $payload['action'];
            }))
            ->willReturn(['value' => true]);

        $locator = new Locator($this->transport, 'page1', '.element');

        $locator->waitForVisible();
    }

    public function testWaitForVisibleTimeout(): void
    {
        $this->transport
            ->expects($this->atLeastOnce())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.isVisible' === $payload['action'];
            }))
            ->willReturn(['value' => false]);

        $locator = new Locator($this->transport, 'page1', '.element');

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Element not visible (timeout: 1000ms)');

        $locator->waitForVisible(['timeout' => 1000]);
    }

    public function testWaitForTextContains(): void
    {
        $callCount = 0;
        $this->transport
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function ($payload) use (&$callCount) {
                ++$callCount;

                if ('locator.textContent' === $payload['action']) {
                    return ['value' => 1 === $callCount ? 'Loading...' : 'Success: Data loaded'];
                }

                return [];
            });

        $locator = new Locator($this->transport, 'page1', '.status');

        $locator->waitForText('Success');
    }

    public function testWaitForHidden(): void
    {
        $callCount = 0;
        $this->transport
            ->expects($this->exactly(2))
            ->method('send')
            ->willReturnCallback(function ($payload) use (&$callCount) {
                ++$callCount;

                if ('locator.isHidden' === $payload['action']) {
                    return ['value' => 2 === $callCount];
                }

                return [];
            });

        $locator = new Locator($this->transport, 'page1', '.modal');

        $locator->waitForHidden();
    }

    public function testFillWaitsForActionable(): void
    {
        $callCount = 0;
        $this->transport
            ->expects($this->exactly(3))
            ->method('send')
            ->willReturnCallback(function ($payload) use (&$callCount) {
                ++$callCount;

                if ($callCount <= 2) {
                    if ('locator.isVisible' === $payload['action']) {
                        return ['value' => true];
                    }
                    if ('locator.isEnabled' === $payload['action']) {
                        return ['value' => true];
                    }
                }

                if (3 === $callCount) {
                    $this->assertEquals('locator.fill', $payload['action']);
                    $this->assertEquals('test value', $payload['value']);

                    return [];
                }

                return [];
            });

        $locator = new Locator($this->transport, 'page1', 'input[type="text"]');

        $locator->fill('test value');
    }

    public function testWaitForAttached(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.isAttached' === $payload['action'];
            }))
            ->willReturn(['value' => true]);

        $locator = new Locator($this->transport, 'page1', '.dynamic-element');

        $locator->waitForAttached();
    }

    public function testWaitForDetached(): void
    {
        $this->transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.isAttached' === $payload['action'];
            }))
            ->willReturn(['value' => false]);

        $locator = new Locator($this->transport, 'page1', '.removed-element');

        $locator->waitForDetached();
    }
}
