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
use Playwright\Locator\Options\GetByRoleOptions;
use Playwright\Locator\Options\LocatorOptions;

#[CoversClass(GetByRoleOptions::class)]
final class GetByRoleOptionsTest extends TestCase
{
    public function testToArrayMergesRoleSpecificAndLocatorOptions(): void
    {
        $options = GetByRoleOptions::from([
            'hasText' => 'child',
            'checked' => true,
            'includeHidden' => true,
            'level' => '3',
            'name' => new class implements \Stringable {
                public function __toString(): string
                {
                    return 'Save';
                }
            },
            'timeout' => 250,
        ]);

        $result = $options->toArray();

        $this->assertSame('child', $result['hasText']);
        $this->assertTrue($result['checked']);
        $this->assertTrue($result['includeHidden']);
        $this->assertSame(3, $result['level']);
        $this->assertSame('Save', $result['name']);
        $this->assertSame(250, $result['timeout']);
    }

    public function testFromArrayAcceptsIntLevel(): void
    {
        $options = GetByRoleOptions::from(['level' => 3]);
        $this->assertSame(3, $options->level);
    }

    public function testFromArrayAcceptsStringName(): void
    {
        $options = GetByRoleOptions::from(['name' => 'Submit']);
        $this->assertSame('Submit', $options->name);
    }

    public function testFromArrayAcceptsPressedOptions(): void
    {
        // Boolean true
        $options1 = GetByRoleOptions::from(['pressed' => true]);
        $this->assertTrue($options1->pressed);

        // Boolean false
        $options2 = GetByRoleOptions::from(['pressed' => false]);
        $this->assertFalse($options2->pressed);

        // String "mixed"
        $options3 = GetByRoleOptions::from(['pressed' => 'mixed']);
        $this->assertSame('mixed', $options3->pressed);
    }

    public function testFromArrayRejectsInvalidPressedOption(): void
    {
        $this->expectExceptionMessage('getByRole option "pressed" must be boolean or "mixed".');
        GetByRoleOptions::from(['pressed' => 'invalid']);
    }

    public function testFromArrayRejectsInvalidCheckedFlag(): void
    {
        $this->expectExceptionMessage('getByRole option "checked" must be boolean.');

        GetByRoleOptions::from(['checked' => 'yes']);
    }

    public function testFromArrayRejectsInvalidLevel(): void
    {
        $this->expectExceptionMessage('getByRole option "level" must be an integer.');

        GetByRoleOptions::from(['level' => 1.5]);
    }

    public function testCanBeInstantiatedDirectly(): void
    {
        $options = new GetByRoleOptions(
            pressed: true,
            exact: true,
            locatorOptions: new LocatorOptions(hasNotText: 'disabled')
        );

        $result = $options->toArray();

        $this->assertTrue($result['pressed']);
        $this->assertTrue($result['exact']);
        $this->assertSame('disabled', $result['hasNotText']);
    }

    public function testExactOption(): void
    {
        $options = GetByRoleOptions::from(['exact' => true]);
        $this->assertTrue($options->exact);
        $this->assertTrue($options->toArray()['exact']);
    }

    public function testFromReturnsSameInstance(): void
    {
        $options = new GetByRoleOptions(checked: true);
        $this->assertSame($options, GetByRoleOptions::from($options));
    }

    public function testFromArrayRejectsInvalidName(): void
    {
        $this->expectExceptionMessage('getByRole option "name" must be stringable.');
        GetByRoleOptions::from(['name' => []]);
    }

    public function testFromArrayHandlesNullValues(): void
    {
        $options = GetByRoleOptions::from([
            'checked' => null,
            'level' => null,
            'name' => null,
        ]);

        $result = $options->toArray();

        $this->assertArrayNotHasKey('checked', $result);
        $this->assertArrayNotHasKey('level', $result);
        $this->assertArrayNotHasKey('name', $result);
    }
}
