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
use Playwright\Locator\Locator;
use Playwright\Transport\TransportInterface;

#[CoversClass(Locator::class)]
final class LocatorEvaluateNormalizerTest extends TestCase
{
    public function testNormalizesReturnBodyToFunctionWithElement(): void
    {
        $transport = $this->createMock(TransportInterface::class);

        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.evaluate' === $payload['action']
                    && '(el, arg) => { return el.textContent; }' === $payload['expression'];
            }))
            ->willReturn(['value' => 'hello']);

        $locator = new Locator($transport, 'page1', '.title');
        $result = $locator->evaluate('return el.textContent;');
        $this->assertSame('hello', $result);
    }

    public function testLeavesPlainExpressionUntouched(): void
    {
        $transport = $this->createMock(TransportInterface::class);

        $transport
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($payload) {
                return 'locator.evaluate' === $payload['action']
                    && 'element.textContent' === $payload['expression'];
            }))
            ->willReturn(['value' => 'ok']);

        $locator = new Locator($transport, 'page1', '.title');
        $result = $locator->evaluate('element.textContent');
        $this->assertSame('ok', $result);
    }
}
