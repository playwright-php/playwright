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
use Playwright\Locator\LocatorInterface;
use Playwright\Locator\Options\LocatorOptions;

#[CoversClass(LocatorOptions::class)]
final class LocatorOptionsTest extends TestCase
{
    public function testToArrayIncludesKnownAndExtraKeys(): void
    {
        $hasLocator = $this->createMock(LocatorInterface::class);

        $options = LocatorOptions::from([
            'has' => $hasLocator,
            'hasText' => 'Submit',
            'strict' => true,
            'timeout' => 5000,
        ]);

        $result = $options->toArray();

        $this->assertSame($hasLocator, $result['has']);
        $this->assertSame('Submit', $result['hasText']);
        $this->assertTrue($result['strict']);
        $this->assertSame(5000, $result['timeout']);
    }

    public function testFromArrayRejectsInvalidStrictFlag(): void
    {
        $this->expectExceptionMessage('Locator option "strict" must be boolean.');

        LocatorOptions::from(['strict' => 'yes']);
    }

    public function testFromReturnsSameInstance(): void
    {
        $options = new LocatorOptions(strict: true);
        $this->assertSame($options, LocatorOptions::from($options));
    }

    public function testFromArrayRejectsInvalidLocator(): void
    {
        $this->expectExceptionMessage('Locator option "has" must be a Locator instance or null.');
        LocatorOptions::from(['has' => 'invalid']);
    }

    public function testFromArrayRejectsInvalidString(): void
    {
        $this->expectExceptionMessage('Locator option "hasText" must be stringable.');
        LocatorOptions::from(['hasText' => []]);
    }

    public function testFromArrayHandlesNullValues(): void
    {
        $options = LocatorOptions::from([
            'has' => null,
            'hasText' => null,
        ]);

        $result = $options->toArray();

        $this->assertArrayNotHasKey('has', $result);
        $this->assertArrayNotHasKey('hasText', $result);
    }

    public function testFromArrayIgnoresNonStringKeys(): void
    {
        $options = LocatorOptions::from([
            'valid' => 'value',
            0 => 'ignored',
        ]);

        $result = $options->toArray();

        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayNotHasKey(0, $result);
    }
}
