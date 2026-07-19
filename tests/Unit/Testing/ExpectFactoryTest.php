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

namespace Playwright\Tests\Unit\Testing;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Locator\LocatorInterface;
use Playwright\Testing\ExpectInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tracing\TracingInterface;

use function Playwright\Testing\expect;

#[CoversTrait(PlaywrightTestCaseTrait::class)]
#[CoversFunction('Playwright\Testing\expect')]
final class ExpectFactoryTest extends TestCase
{
    private function createVisibleLocator(): LocatorInterface
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('isVisible')->willReturn(true);
        $locator->method('getSelector')->willReturn('#x');

        return $locator;
    }

    public function testTheTraitInjectsTheContextTracing(): void
    {
        $tracing = $this->createMock(TracingInterface::class);
        $tracing->expects($this->once())->method('group');
        $tracing->expects($this->once())->method('groupEnd');

        $context = $this->createMock(BrowserContextInterface::class);
        $context->method('tracing')->willReturn($tracing);

        $harness = new class('harness') extends TestCase {
            use PlaywrightTestCaseTrait;

            public function callExpect(BrowserContextInterface $context, LocatorInterface $subject): ExpectInterface
            {
                $this->context = $context;

                return $this->expect($subject);
            }
        };

        $harness->callExpect($context, $this->createVisibleLocator())->toBeVisible();
    }

    public function testTheExpectFunctionAcceptsATracingHandle(): void
    {
        $tracing = $this->createMock(TracingInterface::class);
        $tracing->expects($this->once())->method('group')->with('expect(#x).toBeVisible');
        $tracing->expects($this->once())->method('groupEnd');

        expect($this->createVisibleLocator(), $tracing)->toBeVisible();
    }

    public function testTheExpectFunctionWorksWithoutTracing(): void
    {
        expect($this->createVisibleLocator())->toBeVisible();

        self::assertTrue(true);
    }
}
