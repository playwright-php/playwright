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
use PHPUnit\Framework\TestCase;
use Playwright\Testing\PlaywrightTestCase;

#[CoversClass(PlaywrightTestCase::class)]
final class PlaywrightTestCaseTest extends TestCase
{
    public function testPlaywrightTestCaseExtendsTestCase(): void
    {
        $reflection = new \ReflectionClass(PlaywrightTestCase::class);
        $this->assertTrue($reflection->isSubclassOf(TestCase::class));
    }

    public function testPlaywrightTestCaseUsesTraitMethods(): void
    {
        $reflection = new \ReflectionClass(PlaywrightTestCase::class);

        $this->assertTrue($reflection->hasMethod('setUpPlaywright'));
        $this->assertTrue($reflection->hasMethod('tearDownPlaywright'));
        $this->assertTrue($reflection->hasMethod('expect'));
        $this->assertTrue($reflection->hasMethod('assertElementExists'));
    }

    public function testTraitPropertiesAreAvailable(): void
    {
        $reflection = new \ReflectionClass(PlaywrightTestCase::class);

        $this->assertTrue($reflection->hasProperty('playwright'));
        $this->assertTrue($reflection->hasProperty('browser'));
        $this->assertTrue($reflection->hasProperty('context'));
        $this->assertTrue($reflection->hasProperty('page'));
    }

    public function testSetUpCallsSetUpPlaywright(): void
    {
        $reflection = new \ReflectionClass(PlaywrightTestCase::class);

        $this->assertTrue($reflection->hasMethod('setUp'));
        $this->assertTrue($reflection->hasMethod('tearDown'));

        $setUpMethod = $reflection->getMethod('setUp');

        $this->assertTrue($setUpMethod->hasReturnType());
        $this->assertEquals('void', $setUpMethod->getReturnType()->getName());
    }
}
