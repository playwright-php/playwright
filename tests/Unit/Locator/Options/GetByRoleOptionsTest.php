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
            locatorOptions: new LocatorOptions(hasNotText: 'disabled')
        );

        $result = $options->toArray();

        $this->assertTrue($result['pressed']);
        $this->assertSame('disabled', $result['hasNotText']);
    }
}
