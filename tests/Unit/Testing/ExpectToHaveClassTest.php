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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playwright\Locator\LocatorInterface;
use Playwright\Testing\Expect;

#[CoversClass(Expect::class)]
final class ExpectToHaveClassTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string|string[]}>
     */
    public static function matchingProvider(): iterable
    {
        yield 'single class' => ['primary', 'primary'];
        yield 'two classes as a string' => ['primary active', 'primary active'];
        yield 'two classes as an array' => ['primary active', ['primary', 'active']];
        yield 'surrounding whitespace' => ['  primary active  ', 'primary active'];
        yield 'repeated whitespace' => ["primary\n  active", 'primary active'];
    }

    /**
     * @return iterable<string, array{string, string|string[]}>
     */
    public static function mismatchingProvider(): iterable
    {
        yield 'extra class on the element' => ['primary active', 'primary'];
        yield 'missing class on the element' => ['primary', 'primary active'];
        yield 'different order' => ['active primary', 'primary active'];
        yield 'unrelated class' => ['primary', 'secondary'];
        yield 'empty class attribute' => ['', 'primary'];
    }

    /**
     * @param string|string[] $expected
     */
    #[DataProvider('matchingProvider')]
    public function testAnExactClassListPasses(string $actual, string|array $expected): void
    {
        (new Expect($this->locatorWithClass($actual)))->toHaveClass($expected);

        $this->assertTrue(true);
    }

    /**
     * @param string|string[] $expected
     */
    #[DataProvider('mismatchingProvider')]
    public function testAnythingOtherThanTheExactListFails(string $actual, string|array $expected): void
    {
        $this->expectException(\Throwable::class);

        (new Expect($this->locatorWithClass($actual)))->withTimeout(0)->toHaveClass($expected);
    }

    public function testANegatedAssertionPassesOnADifferentList(): void
    {
        (new Expect($this->locatorWithClass('primary active')))->not()->toHaveClass('primary');

        $this->assertTrue(true);
    }

    public function testAMissingClassAttributeFails(): void
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('getAttribute')->with('class')->willReturn(null);
        $locator->method('getSelector')->willReturn('#x');

        $this->expectException(\Throwable::class);

        (new Expect($locator))->withTimeout(0)->toHaveClass('primary');
    }

    private function locatorWithClass(string $class): LocatorInterface
    {
        $locator = $this->createMock(LocatorInterface::class);
        $locator->method('getAttribute')->with('class')->willReturn($class);
        $locator->method('getSelector')->willReturn('#x');

        return $locator;
    }
}
