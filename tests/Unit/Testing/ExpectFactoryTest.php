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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Playwright\Browser\BrowserContextInterface;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;
use Playwright\Testing\Expect;
use Playwright\Testing\ExpectDecorator;
use Playwright\Testing\ExpectInterface;
use Playwright\Testing\PlaywrightTestCaseTrait;
use Playwright\Tracing\TracingInterface;

use function Playwright\Testing\expect;

#[CoversTrait(PlaywrightTestCaseTrait::class)]
#[CoversFunction('Playwright\Testing\expect')]
#[CoversClass(Expect::class)]
#[CoversClass(ExpectDecorator::class)]
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

        $harness->callExpect($context, $this->createVisibleLocator())
            ->withTimeout(100)
            ->toBeVisible();
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

        $this->assertTrue(true);
    }

    public function testTheExpectFunctionExposesLocatorAssertions(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('isAttached')->willReturn(true);
        $locator->method('getSelector')->willReturn('#x');

        expect($locator)->toBeAttached();
    }

    public function testTheExpectFunctionKeepsLegacyToHaveTextContainsSemantics(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->once())->method('textContent')->willReturn('Welcome Simon');
        $locator->method('getSelector')->willReturn('#x');

        expect($locator)->toHaveText('Welcome');
    }

    public function testLocatorAssertionsRejectAPageSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        expect($this->createMock(PageInterface::class))->toBeVisible();
    }

    public function testPageAssertionsRejectALocatorSubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        expect($this->createVisibleLocator())->toHaveTitle('Title');
    }

    public function testNegationDoesNotLeakToTheNextAssertion(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->expects($this->exactly(2))->method('isVisible')->willReturn(false, true);
        $locator->method('getSelector')->willReturn('#x');
        $expect = expect($locator)->withPollInterval(0);

        $expect->not()->toBeVisible();
        $expect->toBeVisible();
    }
}
