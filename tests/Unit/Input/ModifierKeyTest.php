<?php

declare(strict_types=1);

/*
 * This file is part of the playwright-php/playwright package.
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PlaywrightPHP\Tests\Unit\Input;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PlaywrightPHP\Exception\InvalidArgumentException;
use PlaywrightPHP\Input\ModifierKey;

#[CoversClass(ModifierKey::class)]
final class ModifierKeyTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('Alt', ModifierKey::Alt->value);
        $this->assertSame('Control', ModifierKey::Control->value);
        $this->assertSame('Meta', ModifierKey::Meta->value);
        $this->assertSame('Shift', ModifierKey::Shift->value);
    }

    #[DataProvider('provideFromStringData')]
    public function testFromString(ModifierKey $expected, string $input): void
    {
        $this->assertSame($expected, ModifierKey::fromString($input));
    }

    public static function provideFromStringData(): array
    {
        return [
            'alt lowercase' => [ModifierKey::Alt, 'alt'],
            'alt uppercase' => [ModifierKey::Alt, 'Alt'],
            'control lowercase' => [ModifierKey::Control, 'control'],
            'control uppercase' => [ModifierKey::Control, 'Control'],
            'control short' => [ModifierKey::Control, 'ctrl'],
            'meta lowercase' => [ModifierKey::Meta, 'meta'],
            'meta uppercase' => [ModifierKey::Meta, 'Meta'],
            'meta cmd' => [ModifierKey::Meta, 'cmd'],
            'meta command' => [ModifierKey::Meta, 'command'],
            'shift lowercase' => [ModifierKey::Shift, 'shift'],
            'shift uppercase' => [ModifierKey::Shift, 'Shift'],
        ];
    }

    public function testFromStringWithInvalidModifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown modifier key: "invalid".');

        ModifierKey::fromString('invalid');
    }
}
